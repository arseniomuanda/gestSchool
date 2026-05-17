<?php

namespace Tests\Feature\Notifications;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Notifications\Concerns\WithNotificationsSetup;
use Tests\TestCase;

class NotificationTriggersTest extends TestCase
{
    use RefreshDatabase;
    use WithNotificationsSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootNotificationsEnvironment();
    }

    public function test_publishing_announcement_to_turma_emails_guardians(): void
    {
        Mail::fake();
        $admin = $this->createUserWithRole('director_geral');
        $env = $this->createTurmaComAluno('encarregado@example.com');

        $this->actingAs($admin)->post('/comunicados', [
            'titulo' => 'Aviso da Direcção',
            'conteudo' => 'Reunião 10h.',
            'alcance' => 'turma',
            'turma_id' => $env['turma']->id,
            'publicado_em' => now()->toDateTimeString(),
        ])->assertRedirect('/comunicados');

        $log = Notification::query()
            ->where('event_key', 'comunicado_publicado')
            ->where('recipient_address', 'encarregado@example.com')
            ->first();

        $this->assertNotNull($log, 'Expected a notification log entry for the guardian.');
        $this->assertSame('sent', $log->status);
        $this->assertSame('email', $log->channel);
        $this->assertStringContainsString('Aviso da Direcção', $log->subject);
    }

    public function test_announcement_in_draft_does_not_notify(): void
    {
        Mail::fake();
        $admin = $this->createUserWithRole('director_geral');
        $env = $this->createTurmaComAluno();

        $this->actingAs($admin)->post('/comunicados', [
            'titulo' => 'Rascunho',
            'conteudo' => 'Ainda não publicar',
            'alcance' => 'turma',
            'turma_id' => $env['turma']->id,
            'publicado_em' => null,
        ])->assertRedirect();

        $this->assertSame(0, Notification::where('event_key', 'comunicado_publicado')->count());
    }

    public function test_announcement_with_future_publish_date_does_not_notify_now(): void
    {
        Mail::fake();
        $admin = $this->createUserWithRole('director_geral');
        $env = $this->createTurmaComAluno();

        $this->actingAs($admin)->post('/comunicados', [
            'titulo' => 'Futuro',
            'conteudo' => 'Daqui a uma semana',
            'alcance' => 'turma',
            'turma_id' => $env['turma']->id,
            'publicado_em' => now()->addWeek()->toDateTimeString(),
        ])->assertRedirect();

        $this->assertSame(0, Notification::where('event_key', 'comunicado_publicado')->count());
    }

    public function test_faltas_excessivas_command_dispatches_and_respects_cooldown(): void
    {
        Mail::fake();
        config(['escola.faltas_excessivas_limite' => 3]);

        $env = $this->createTurmaComAluno('enc-faltas@example.com');
        $this->darFaltas($env, 4);

        $this->artisan('notifications:faltas-excessivas')->assertExitCode(0);

        $log = Notification::where('event_key', 'faltas_excessivas')
            ->where('recipient_address', 'enc-faltas@example.com')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);

        // Segunda execução não deve disparar nova notificação (cooldown)
        $this->artisan('notifications:faltas-excessivas')->assertExitCode(0);
        $count = Notification::where('event_key', 'faltas_excessivas')
            ->where('recipient_address', 'enc-faltas@example.com')
            ->count();
        $this->assertSame(1, $count, 'Cooldown should prevent re-sending within the window.');
    }

    public function test_faltas_excessivas_dry_run_does_not_send(): void
    {
        Mail::fake();
        config(['escola.faltas_excessivas_limite' => 1]);
        $env = $this->createTurmaComAluno();
        $this->darFaltas($env, 2);

        $this->artisan('notifications:faltas-excessivas --dry')->assertExitCode(0);

        $this->assertSame(0, Notification::where('event_key', 'faltas_excessivas')->count());
    }
}
