<?php

namespace Tests\Feature\Horarios;

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\Classe;
use App\Models\Encarregado;
use App\Models\Matricula;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncarregadoHorarioAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    /**
     * Cria um encarregado ligado a um aluno matriculado numa turma; devolve
     * (encUser, turma, outraTurma) onde outraTurma não tem aluno deste encarregado.
     */
    protected function ambiente(): array
    {
        $ano = AnoLectivo::create([
            'codigo' => '2026/2027', 'inicio' => '2026-09-01', 'fim' => '2027-07-31', 'activo' => true,
        ]);
        $classe = Classe::create(['nome' => '7ª Classe', 'ordem' => 7, 'nivel' => 'ensino_base']);
        $turma = Turma::create(['classe_id' => $classe->id, 'ano_lectivo_id' => $ano->id, 'nome' => 'A', 'capacidade' => 40]);
        $outraTurma = Turma::create(['classe_id' => $classe->id, 'ano_lectivo_id' => $ano->id, 'nome' => 'B', 'capacidade' => 40]);

        $alunoUser = User::factory()->create();
        $aluno = Aluno::create(['user_id' => $alunoUser->id, 'numero_processo' => 'PR-'.uniqid()]);

        $encUser = User::factory()->create();
        $encUser->assignRole('encarregado');
        $enc = Encarregado::create(['user_id' => $encUser->id]);
        $aluno->encarregados()->attach($enc->id, ['parentesco' => 'pai', 'principal' => true]);

        Matricula::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'ano_lectivo_id' => $ano->id,
            'numero_matricula' => 'MT-'.uniqid(),
            'data_matricula' => '2026-09-01',
            'estado' => 'activa',
        ]);

        return [$encUser, $turma, $outraTurma];
    }

    public function test_encarregado_pode_ver_horario_da_turma_do_filho(): void
    {
        [$encUser, $turma, $outraTurma] = $this->ambiente();
        $this->actingAs($encUser)
            ->get("/horarios/turma/{$turma->id}")
            ->assertOk();
    }

    public function test_encarregado_recebe_403_em_turma_sem_filho(): void
    {
        [$encUser, $turma, $outraTurma] = $this->ambiente();
        $this->actingAs($encUser)
            ->get("/horarios/turma/{$outraTurma->id}")
            ->assertForbidden();
    }

    public function test_encarregado_nao_pode_aceder_listagem_de_horarios(): void
    {
        [$encUser] = $this->ambiente();
        $this->actingAs($encUser)
            ->get('/horarios')
            ->assertForbidden();
    }

    public function test_encarregado_nao_pode_aceder_create_horario(): void
    {
        [$encUser] = $this->ambiente();
        $this->actingAs($encUser)
            ->get('/horarios/create')
            ->assertForbidden();
    }
}
