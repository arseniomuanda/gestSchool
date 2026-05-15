<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
            AcademicSeeder::class,        // estrutura: anos, classes, cursos, disciplinas, currículo, trimestres
            DemoUsersSeeder::class,       // 7 utilizadores demo
            ProfessoresSeeder::class,     // ~50 professores adicionais
            TurmasSeeder::class,          // turmas (5 anos × ~70 turmas)
            AlunosEncarregadosSeeder::class, // 3000 alunos + encarregados + matrículas históricas
            AtribuicoesSeeder::class,     // professores × turmas × disciplinas
            PautasNotasPresencasSeeder::class, // avaliações, notas, aulas, presenças
            DemoAlunoPedroSeeder::class,  // popula AL-2026-0001 com matrícula activa, notas e presenças
        ]);
    }
}
