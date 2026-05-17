<?php

namespace Tests\Feature\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\Channels\SmsChannel;
use App\Services\Notifications\NotificationSender;
use App\Services\Notifications\OmbalaClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Notifications\Concerns\WithNotificationsSetup;
use Tests\TestCase;

class OmbalaIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use WithNotificationsSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootNotificationsEnvironment();
        Cache::flush();

        config([
            'notifications.sms.enabled' => true,
            'notifications.sms.api_key' => 'a9eb6ea6-test-fake-key',
            'notifications.sms.sender_id' => 'GESTSCHOOL',
            'notifications.sms.api_url' => 'https://api.useombala.ao',
        ]);
    }

    public function test_normalize_phone_strips_country_prefix_and_separators(): void
    {
        $client = new OmbalaClient('k');
        $this->assertSame('921939411', $client->normalizePhone('921939411'));
        $this->assertSame('921939411', $client->normalizePhone('+244 921 939 411'));
        $this->assertSame('921939411', $client->normalizePhone('00244-921939411'));
        $this->assertSame('921939411', $client->normalizePhone('244 921 939 411'));
    }

    public function test_send_sms_success_logs_notification_as_sent(): void
    {
        Http::fake([
            'api.useombala.ao/v1/messages' => Http::response(['status' => 'ok', 'id' => 'msg_1'], 201),
        ]);

        $user = User::factory()->create(['name' => 'Maria']);
        $user->phone = '921939411';
        $user->save();

        $result = app(NotificationSender::class)->dispatch(
            eventKey: 'comunicado_publicado',
            recipients: collect([$user]),
            channels: ['sms'],
            payload: ['titulo' => 'Teste'],
        );

        $this->assertSame(1, $result['sent']);
        $log = Notification::latest('id')->first();
        $this->assertSame('sent', $log->status);
        $this->assertSame('921939411', $log->recipient_address);

        Http::assertSent(function ($req) {
            return $req->url() === 'https://api.useombala.ao/v1/messages'
                && $req->hasHeader('Authorization', 'Token a9eb6ea6-test-fake-key')
                && $req['from'] === 'GESTSCHOOL'
                && $req['to'] === '921939411'
                && str_contains((string) $req['message'], 'Teste');
        });
    }

    public function test_send_sms_http_failure_logs_as_failed_with_error(): void
    {
        Http::fake([
            'api.useombala.ao/v1/messages' => Http::response(['message' => 'Sender not approved'], 400),
        ]);

        $user = User::factory()->create();
        $user->phone = '923000111';
        $user->save();

        $result = app(NotificationSender::class)->dispatch(
            eventKey: 'comunicado_publicado',
            recipients: collect([$user]),
            channels: ['sms'],
            payload: ['titulo' => 'X'],
        );

        $this->assertSame(0, $result['sent']);
        $this->assertSame(1, $result['failed']);
        $log = Notification::latest('id')->first();
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('HTTP 400', $log->error);
        $this->assertStringContainsString('Sender not approved', $log->error);
    }

    public function test_sms_channel_skips_when_disabled(): void
    {
        config(['notifications.sms.enabled' => false]);
        Http::fake();

        $user = User::factory()->create();
        $user->phone = '921000000';
        $user->save();

        $result = app(NotificationSender::class)->dispatch(
            eventKey: 'comunicado_publicado',
            recipients: collect([$user]),
            channels: ['sms'],
            payload: ['titulo' => 'Y'],
        );

        $this->assertSame(1, $result['failed']);
        Http::assertNothingSent();
        $this->assertStringContainsString('OMBALA_ENABLED', Notification::latest('id')->first()->error);
    }

    public function test_sms_channel_errors_when_api_key_missing(): void
    {
        config(['notifications.sms.api_key' => null]);
        Http::fake();

        $user = User::factory()->create();
        $user->phone = '921000000';
        $user->save();

        $result = app(NotificationSender::class)->dispatch(
            eventKey: 'comunicado_publicado',
            recipients: collect([$user]),
            channels: ['sms'],
            payload: ['titulo' => 'Z'],
        );

        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('OMBALA_API_KEY', Notification::latest('id')->first()->error);
        Http::assertNothingSent();
    }

    public function test_credits_returns_parsed_balance_and_caches(): void
    {
        Http::fake([
            'api.useombala.ao/v1/credits' => Http::response(['credits' => 1234], 200),
        ]);

        $client = new OmbalaClient('k', 'https://api.useombala.ao');
        $this->assertSame(1234, $client->credits());

        // Second call uses cache — no extra HTTP request
        $this->assertSame(1234, $client->credits());
        Http::assertSentCount(1);
    }

    public function test_credits_returns_null_on_failure(): void
    {
        Http::fake([
            'api.useombala.ao/v1/credits' => Http::response('boom', 500),
        ]);
        $client = new OmbalaClient('k', 'https://api.useombala.ao');
        $this->assertNull($client->credits(force: true));
    }

    public function test_credits_returns_null_when_not_configured(): void
    {
        $client = new OmbalaClient('', 'https://api.useombala.ao');
        $this->assertNull($client->credits());
    }

    public function test_approved_senders_parses_array_response(): void
    {
        Http::fake([
            'api.useombala.ao/v1/senders/approved' => Http::response([
                ['name' => 'GESTSCHOOL'],
                ['name' => 'ESCOLA_LWINI'],
            ], 200),
        ]);
        $client = new OmbalaClient('k', 'https://api.useombala.ao');
        $this->assertSame(['GESTSCHOOL', 'ESCOLA_LWINI'], $client->approvedSenders());
    }

    public function test_send_invalidates_credits_cache(): void
    {
        Cache::put(OmbalaClient::CREDITS_CACHE_KEY, 100, 60);
        Http::fake([
            'api.useombala.ao/v1/messages' => Http::response(['ok' => true], 201),
        ]);

        $client = new OmbalaClient('k', 'https://api.useombala.ao');
        $client->sendSms('921939411', 'oi', 'GESTSCHOOL');

        $this->assertNull(Cache::get(OmbalaClient::CREDITS_CACHE_KEY));
    }
}
