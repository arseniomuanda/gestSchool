<?php

namespace Tests\Feature\Notifications;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Notifications\Concerns\WithNotificationsSetup;
use Tests\TestCase;

class NotificationAccessTest extends TestCase
{
    use RefreshDatabase;
    use WithNotificationsSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootNotificationsEnvironment();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/notificacoes')->assertRedirect('/login');
    }

    public function test_non_admin_role_cannot_access_module(): void
    {
        $professor = $this->createUserWithRole('professor');
        $this->actingAs($professor)->get('/notificacoes')->assertForbidden();
    }

    public function test_director_pedagogico_cannot_access_module(): void
    {
        $u = $this->createUserWithRole('director_pedagogico');
        $this->actingAs($u)->get('/notificacoes')->assertForbidden();
    }

    public function test_director_geral_can_access_index(): void
    {
        $admin = $this->createUserWithRole('director_geral');
        $this->actingAs($admin)
            ->get('/notificacoes')
            ->assertOk()
            ->assertSee('Notificações');
    }

    public function test_director_geral_can_open_settings_and_templates(): void
    {
        $admin = $this->createUserWithRole('director_geral');
        $this->actingAs($admin)->get('/notificacoes/settings')->assertOk();
        $this->actingAs($admin)->get('/notificacoes/templates')->assertOk();
        $this->actingAs($admin)->get('/notificacoes/historico')->assertOk();
        $this->actingAs($admin)->get('/notificacoes/sms-creditos')->assertOk();
    }
}
