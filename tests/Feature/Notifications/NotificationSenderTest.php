<?php

namespace Tests\Feature\Notifications;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Notifications\NotificationSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Notifications\Concerns\WithNotificationsSetup;
use Tests\TestCase;

class NotificationSenderTest extends TestCase
{
    use RefreshDatabase;
    use WithNotificationsSetup;

    protected NotificationSender $sender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootNotificationsEnvironment();
        $this->sender = app(NotificationSender::class);
    }

    public function test_dispatches_email_renders_template_and_logs_history(): void
    {
        Mail::fake();
        $user = User::factory()->create(['name' => 'João Manuel', 'email' => 'joao@example.com']);

        $result = $this->sender->dispatch(
            eventKey: 'comunicado_publicado',
            recipients: collect([$user]),
            channels: ['email'],
            payload: ['titulo' => 'Reunião pais', 'mensagem' => 'Sábado 9h.'],
        );

        $this->assertSame(['sent' => 1, 'failed' => 0, 'skipped' => 0], $result);

        $log = Notification::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertSame('email', $log->channel);
        $this->assertSame('comunicado_publicado', $log->event_key);
        $this->assertSame('joao@example.com', $log->recipient_address);
        $this->assertStringContainsString('Reunião pais', $log->subject);
        $this->assertStringContainsString('João Manuel', $log->body_preview);
        $this->assertStringContainsString('Sábado 9h.', $log->body_preview);
    }

    public function test_recipient_without_email_is_skipped(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $user->email = '';
        $user->saveQuietly();

        $result = $this->sender->dispatch(
            eventKey: 'comunicado_publicado',
            recipients: collect([$user]),
            channels: ['email'],
            payload: ['titulo' => 'X'],
        );

        $this->assertSame(0, $result['sent']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_sms_channel_logs_failure_when_disabled(): void
    {
        $user = User::factory()->create();
        $user->phone = '+244900000000';
        $user->save();

        $result = $this->sender->dispatch(
            eventKey: 'comunicado_publicado',
            recipients: collect([$user]),
            channels: ['sms'],
            payload: ['titulo' => 'Y'],
        );

        $this->assertSame(0, $result['sent']);
        $this->assertSame(1, $result['failed']);

        $log = Notification::latest('id')->first();
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('SMS', $log->error);
    }

    public function test_inactive_template_skips_dispatch(): void
    {
        NotificationTemplate::where('event_key', 'comunicado_publicado')->update(['active' => false]);
        $user = User::factory()->create();

        $result = $this->sender->dispatch(
            eventKey: 'comunicado_publicado',
            recipients: collect([$user]),
            channels: ['email'],
            payload: [],
        );

        $this->assertSame(0, $result['sent']);
        $this->assertSame(0, $result['failed']);
    }
}
