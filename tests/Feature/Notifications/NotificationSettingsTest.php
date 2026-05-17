<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationTemplate;
use App\Services\Notifications\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Notifications\Concerns\WithNotificationsSetup;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;
    use WithNotificationsSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootNotificationsEnvironment();
    }

    public function test_settings_page_shows_env_values(): void
    {
        $admin = $this->createUserWithRole('director_geral');

        $this->actingAs($admin)
            ->get('/notificacoes/settings')
            ->assertOk()
            ->assertSee(config('mail.from.address'));
    }

    public function test_settings_reads_email_config_from_env(): void
    {
        $this->assertSame(config('mail.from.address'), Settings::get('email.from_address'));
        $this->assertSame(config('mail.from.name'), Settings::get('email.from_name'));
    }

    public function test_settings_reads_sms_config_from_env(): void
    {
        config(['app.env' => 'testing']);
        // Settings::get usa env() — testar via OMBALA_* não definida deve dar default
        $this->assertSame('gestSchool', Settings::get('sms.sender_id'));
        $this->assertFalse((bool) Settings::get('sms.enabled'));
    }

    public function test_test_email_endpoint_works_with_log_mailer(): void
    {
        $admin = $this->createUserWithRole('director_geral');
        $this->actingAs($admin)
            ->post('/notificacoes/settings/email/test', ['email' => 'test@example.com'])
            ->assertRedirect();
        // session should carry status flash (no error)
        $this->assertNull(session('error'));
    }

    public function test_template_can_be_edited(): void
    {
        $admin = $this->createUserWithRole('director_geral');
        $template = NotificationTemplate::where('event_key', 'comunicado_publicado')->where('channel', 'email')->first();
        $this->assertNotNull($template);

        $this->actingAs($admin)->put("/notificacoes/templates/{$template->id}", [
            'subject' => 'Novo assunto teste',
            'body' => 'Corpo modificado com {{titulo}}',
            'active' => '1',
        ])->assertRedirect('/notificacoes/templates');

        $template->refresh();
        $this->assertSame('Novo assunto teste', $template->subject);
        $this->assertStringContainsString('Corpo modificado', $template->body);
    }

    public function test_sms_credit_request_is_persisted(): void
    {
        $admin = $this->createUserWithRole('director_geral');

        $this->actingAs($admin)->post('/notificacoes/sms-creditos', [
            'quantity_requested' => 5000,
            'notes' => 'Reforço para boletim do 1.º trimestre',
        ])->assertRedirect();

        $this->assertDatabaseHas('sms_credit_requests', [
            'requested_by_user_id' => $admin->id,
            'quantity_requested' => 5000,
            'status' => 'pendente',
        ]);
    }
}
