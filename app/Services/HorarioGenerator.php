<?php

namespace App\Services;

use App\Models\Atribuicao;
use App\Models\Horario;
use App\Models\Turma;
use Illuminate\Support\Collection;

/**
 * Auto-gerador de horário por turma. Heurística greedy: para cada atribuição
 * expande em N "blocos de aula" onde N = disciplina.carga_horaria_semanal e
 * coloca-os em slots livres, evitando:
 *
 *  - conflito do professor com horários de outras turmas (mesmo professor
 *    a leccionar 2 turmas ao mesmo tempo)
 *  - mesma disciplina 2× consecutivos no mesmo dia (preferimos variedade)
 *  - >50% dos tempos de uma disciplina pesada no mesmo dia
 *  - colocar disciplinas pesadas em "horas difíceis" (config)
 *
 * Devolve uma matriz `[dia][tempo] => ['atribuicao_id' => ?, 'sala' => '']`
 * pronta a ser injectada no editor Alpine. Não persiste nada: o utilizador
 * revê e grava via o submit normal de `bulkTurmaStore`.
 *
 * Se a carga horária total > slots disponíveis, devolve o que coube + lista
 * de blocos não colocados em `$generator->unplaced`.
 */
class HorarioGenerator
{
    /** @var array<array{atribuicao_id:int, disciplina_id:int}> blocos não colocados */
    public array $unplaced = [];

    public function propor(Turma $turma): array
    {
        $atribuicoes = Atribuicao::with('disciplina', 'professor')
            ->where('turma_id', $turma->id)
            ->where('ano_lectivo_id', $turma->ano_lectivo_id)
            ->get();

        $diasLectivos = config('escola.dias_lectivos', [1, 2, 3, 4, 5]);
        $tempos = array_keys(config('escola.tempos_lectivos'));
        sort($tempos);
        $pesadas = collect(config('escola.disciplinas_pesadas', []))->map(fn ($s) => strtoupper($s));
        $horasDificeis = collect(config('escola.horas_dificeis', []))
            ->map(fn ($par) => $par[0] . ':' . $par[1])->all();
        $manhaCutoff = max(array_slice($tempos, 0, (int) ceil(count($tempos) / 2)));

        // 1) Expandir cada atribuição em blocos
        $blocos = [];
        foreach ($atribuicoes as $a) {
            $n = (int) ($a->disciplina->carga_horaria_semanal ?? 0);
            for ($i = 0; $i < $n; $i++) {
                $blocos[] = [
                    'atribuicao_id' => $a->id,
                    'disciplina_id' => $a->disciplina_id,
                    'professor_id' => $a->professor_id,
                    'eh_pesada' => $pesadas->contains(strtoupper((string) $a->disciplina->sigla)),
                ];
            }
        }
        // Pesadas primeiro (apanham as melhores horas)
        usort($blocos, fn ($a, $b) => ($b['eh_pesada'] <=> $a['eh_pesada']));

        // 2) Inicializar grelha vazia
        $grelha = [];
        foreach ($diasLectivos as $d) {
            foreach ($tempos as $t) {
                $grelha[$d][$t] = ['atribuicao_id' => '', 'sala' => ''];
            }
        }

        // 3) Conflitos cross-turma do mesmo professor (no ano activo) — set de "diaXtempo:profId"
        $conflitos = Horario::whereHas('atribuicao', fn ($q) => $q
                ->where('turma_id', '!=', $turma->id)
                ->where('ano_lectivo_id', $turma->ano_lectivo_id))
            ->with('atribuicao')
            ->get()
            ->mapWithKeys(fn ($h) => [$h->dia_semana . ':' . $h->tempo . ':' . $h->atribuicao->professor_id => true])
            ->all();

        // 4) Greedy: para cada bloco, escolher o melhor slot livre
        foreach ($blocos as $bloco) {
            $melhor = $this->melhorSlot($bloco, $grelha, $conflitos, $diasLectivos, $tempos, $horasDificeis, $manhaCutoff);
            if ($melhor === null) {
                $this->unplaced[] = $bloco;
                continue;
            }
            [$dia, $tempo] = $melhor;
            $grelha[$dia][$tempo]['atribuicao_id'] = (string) $bloco['atribuicao_id'];
        }

        return $grelha;
    }

    /**
     * Escolhe o slot com menor "penalty" entre os livres.
     * @return array{0:int,1:int}|null
     */
    protected function melhorSlot(array $bloco, array $grelha, array $conflitos, array $diasLectivos, array $tempos, array $horasDificeis, int $manhaCutoff): ?array
    {
        $melhor = null;
        $melhorScore = PHP_INT_MAX;

        foreach ($diasLectivos as $dia) {
            foreach ($tempos as $tempo) {
                if ($grelha[$dia][$tempo]['atribuicao_id'] !== '') continue;
                $key = $dia . ':' . $tempo . ':' . $bloco['professor_id'];
                if (isset($conflitos[$key])) continue;

                $score = $this->scoreSlot($bloco, $dia, $tempo, $grelha, $diasLectivos, $tempos, $horasDificeis, $manhaCutoff);
                if ($score < $melhorScore) {
                    $melhorScore = $score;
                    $melhor = [$dia, $tempo];
                }
            }
        }

        return $melhor;
    }

    /**
     * Score menor = melhor encaixe. Penalidades empilhadas:
     *  +100 mesma disciplina no slot adjacente no mesmo dia
     *  +50  já há ≥2 da mesma disciplina nesse dia (concentração)
     *  +30  pesada em "horas difíceis"
     *  +10  pesada na tarde
     *  +5   gap (deixaria furo isolado entre slots ocupados)
     */
    protected function scoreSlot(array $bloco, int $dia, int $tempo, array $grelha, array $diasLectivos, array $tempos, array $horasDificeis, int $manhaCutoff): int
    {
        $score = 0;
        $atrId = (string) $bloco['atribuicao_id'];

        // Penaliza vizinho consecutivo com mesma disciplina
        foreach ([$tempo - 1, $tempo + 1] as $vizinho) {
            if (isset($grelha[$dia][$vizinho]) && $grelha[$dia][$vizinho]['atribuicao_id'] === $atrId) {
                $score += 100;
            }
        }

        // Penaliza concentração diária
        $jaNoDia = 0;
        foreach ($tempos as $t) {
            if ($grelha[$dia][$t]['atribuicao_id'] === $atrId) $jaNoDia++;
        }
        if ($jaNoDia >= 2) $score += 50;

        if ($bloco['eh_pesada']) {
            if (in_array($dia . ':' . $tempo, $horasDificeis, true)) $score += 30;
            if ($tempo > $manhaCutoff) $score += 10;
        }

        return $score;
    }
}
