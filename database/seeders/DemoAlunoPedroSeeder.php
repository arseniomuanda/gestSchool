<?php

namespace Database\Seeders;

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\Aula;
use App\Models\Avaliacao;
use App\Models\Matricula;
use App\Models\Turma;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Popula o aluno demo AL-2026-0001 com dados académicos completos
 * (matrícula activa + notas em todas as avaliações + presenças em
 * todas as aulas da turma) para que seja possível percorrer todo o
 * fluxo do aluno: perfil, boletim, pautas, presenças e encarregado.
 *
 * Idempotente — pode ser executado várias vezes.
 */
class DemoAlunoPedroSeeder extends Seeder
{
    public function run(): void
    {
        $aluno = Aluno::where('numero_processo', 'AL-2026-0001')->first();
        if (! $aluno) {
            $this->command?->warn('AL-2026-0001 não existe. Correr DemoUsersSeeder primeiro.');

            return;
        }

        $anoActivo = AnoLectivo::where('activo', true)->first();
        if (! $anoActivo) {
            $this->command?->warn('Sem ano lectivo activo.');

            return;
        }

        $turma = $this->turmaAlvo($anoActivo->id);
        if (! $turma) {
            $this->command?->warn('Turma alvo (2ª A do ano activo) não existe. Correr TurmasSeeder primeiro.');

            return;
        }

        $matricula = $this->garantirMatricula($aluno, $anoActivo, $turma);
        $this->command?->info("Matrícula activa: {$matricula->numero_matricula} (turma {$turma->id})");

        $aluno->update([
            'classe' => '2ª Classe',
            'turma' => 'A',
            'ano_lectivo' => $anoActivo->codigo,
            'morada' => 'Rua das Acácias, nº 12, Maianga, Luanda',
            'observacoes' => 'Aluno demo para passeio completo do sistema.',
        ]);

        $this->lancarNotas($matricula);
        $this->lancarPresencas($matricula);
    }

    private function turmaAlvo(int $anoLectivoId): ?Turma
    {
        return Turma::where('ano_lectivo_id', $anoLectivoId)
            ->where('nome', 'A')
            ->whereHas('classe', fn ($q) => $q->where('nome', '2ª'))
            ->first();
    }

    private function garantirMatricula(Aluno $aluno, AnoLectivo $ano, Turma $turma): Matricula
    {
        return Matricula::firstOrCreate(
            [
                'aluno_id' => $aluno->id,
                'ano_lectivo_id' => $ano->id,
            ],
            [
                'turma_id' => $turma->id,
                'numero_matricula' => $this->proximoNumeroMatricula(),
                'data_matricula' => $ano->inicio,
                'estado' => 'activa',
            ]
        );
    }

    private function proximoNumeroMatricula(): string
    {
        $max = (int) (DB::table('matriculas')
            ->where('numero_matricula', 'like', 'M-2026-%')
            ->selectRaw("MAX(CAST(SUBSTRING(numero_matricula FROM 8) AS INTEGER)) as max")
            ->value('max') ?? 0);

        return sprintf('M-2026-%05d', $max + 1);
    }

    private function lancarNotas(Matricula $matricula): void
    {
        $avaliacoes = Avaliacao::whereHas(
            'atribuicao',
            fn ($q) => $q->where('turma_id', $matricula->turma_id)
        )->get();

        $existentes = DB::table('notas')
            ->where('matricula_id', $matricula->id)
            ->pluck('avaliacao_id')
            ->all();

        $ts = now();
        $buffer = [];
        $criadas = 0;

        foreach ($avaliacoes as $av) {
            if (in_array($av->id, $existentes, true)) {
                continue;
            }
            $buffer[] = [
                'avaliacao_id' => $av->id,
                'matricula_id' => $matricula->id,
                'valor' => $this->notaPedro(),
                'observacao' => null,
                'created_at' => $ts,
                'updated_at' => $ts,
            ];
            $criadas++;
        }

        if (! empty($buffer)) {
            DB::table('notas')->insert($buffer);
        }

        $this->command?->info("  → {$criadas} notas lançadas (já existiam ".count($existentes).').');
    }

    private function lancarPresencas(Matricula $matricula): void
    {
        $aulas = Aula::whereHas(
            'atribuicao',
            fn ($q) => $q->where('turma_id', $matricula->turma_id)
        )->get();

        $existentes = DB::table('presencas')
            ->where('matricula_id', $matricula->id)
            ->pluck('aula_id')
            ->all();

        $ts = now();
        $buffer = [];
        $criadas = 0;

        foreach ($aulas as $aula) {
            if (in_array($aula->id, $existentes, true)) {
                continue;
            }
            $r = mt_rand(1, 100);
            $estado = match (true) {
                $r <= 85 => 'presente',
                $r <= 92 => 'falta',
                $r <= 97 => 'falta_justificada',
                default => 'atraso',
            };
            $buffer[] = [
                'aula_id' => $aula->id,
                'matricula_id' => $matricula->id,
                'estado' => $estado,
                'observacao' => null,
                'registado_por' => null,
                'created_at' => $ts,
                'updated_at' => $ts,
            ];
            $criadas++;
        }

        if (! empty($buffer)) {
            DB::table('presencas')->insert($buffer);
        }

        $this->command?->info("  → {$criadas} presenças registadas (já existiam ".count($existentes).').');
    }

    /** Distribuição com média ligeiramente acima da turma (Pedro é bom aluno). */
    private function notaPedro(): float
    {
        $u1 = mt_rand(1, 10000) / 10000;
        $u2 = mt_rand(1, 10000) / 10000;
        $z = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
        $valor = 14.0 + 2.5 * $z;

        return round(max(8.0, min(19.5, $valor)) * 2) / 2;
    }
}
