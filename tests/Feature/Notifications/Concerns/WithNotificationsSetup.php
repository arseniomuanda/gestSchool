<?php

namespace Tests\Feature\Notifications\Concerns;

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\Atribuicao;
use App\Models\Aula;
use App\Models\Classe;
use App\Models\Disciplina;
use App\Models\Encarregado;
use App\Models\Matricula;
use App\Models\Presenca;
use App\Models\Professor;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\NotificationTemplatesSeeder;
use Database\Seeders\RolesPermissionsSeeder;

trait WithNotificationsSetup
{
    protected function bootNotificationsEnvironment(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $this->seed(NotificationTemplatesSeeder::class);
    }

    protected function createUserWithRole(string $role, array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $user->assignRole($role);
        return $user;
    }

    /** Cria toda a árvore: AnoLectivo → Classe → Turma → Aluno → Encarregado → Matricula. */
    protected function createTurmaComAluno(string $encarregadoEmail = 'enc@example.com'): array
    {
        $ano = AnoLectivo::create([
            'codigo' => '2025/2026',
            'inicio' => '2025-09-01',
            'fim' => '2026-07-31',
            'activo' => true,
        ]);
        $classe = Classe::create(['nome' => '7ª Classe', 'ordem' => 7, 'nivel' => 'ensino_base']);
        $turma = Turma::create([
            'classe_id' => $classe->id,
            'ano_lectivo_id' => $ano->id,
            'nome' => 'A',
            'capacidade' => 40,
        ]);

        $alunoUser = User::factory()->create(['name' => 'Aluno Teste']);
        $aluno = Aluno::create([
            'user_id' => $alunoUser->id,
            'numero_processo' => 'PR-'.uniqid(),
        ]);

        $encUser = $this->createUserWithRole('encarregado', ['email' => $encarregadoEmail]);
        $encarregado = Encarregado::create(['user_id' => $encUser->id]);
        $aluno->encarregados()->attach($encarregado->id, ['parentesco' => 'pai', 'principal' => true]);

        $matricula = Matricula::create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'ano_lectivo_id' => $ano->id,
            'numero_matricula' => 'MT-'.uniqid(),
            'data_matricula' => '2025-09-01',
            'estado' => 'activa',
        ]);

        return compact('ano', 'classe', 'turma', 'aluno', 'alunoUser', 'encarregado', 'encUser', 'matricula');
    }

    /** Cria N faltas para a matrícula via Aula+Presenca (necessário ter aula_id NOT NULL). */
    protected function darFaltas(array $env, int $quantas): void
    {
        $profUser = User::factory()->create();
        $professor = Professor::create([
            'user_id' => $profUser->id,
            'numero_professor' => 'P-'.uniqid(),
        ]);
        $disciplina = Disciplina::create(['nome' => 'Matemática '.uniqid(), 'sigla' => 'MAT']);
        $atribuicao = Atribuicao::create([
            'professor_id' => $professor->id,
            'turma_id' => $env['turma']->id,
            'disciplina_id' => $disciplina->id,
            'ano_lectivo_id' => $env['ano']->id,
        ]);

        for ($i = 0; $i < $quantas; $i++) {
            $aula = Aula::create([
                'atribuicao_id' => $atribuicao->id,
                'data' => now()->subDays($i + 1)->toDateString(),
            ]);
            Presenca::create([
                'aula_id' => $aula->id,
                'matricula_id' => $env['matricula']->id,
                'estado' => 'falta',
            ]);
        }
    }
}
