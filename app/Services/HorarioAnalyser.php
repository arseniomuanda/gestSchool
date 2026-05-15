<?php

namespace App\Services;

use App\Models\Atribuicao;
use App\Models\Horario;
use App\Models\Turma;
use Illuminate\Support\Collection;

/**
 * Diagnóstico de horários: detecta lacunas, atribuições não escaladas
 * e divergências de carga horária por disciplina.
 *
 * Heurística determinística — sem chamadas a IA. Serve de fonte para o
 * painel "Diagnóstico" no bulk editor (que duplica a lógica em JS para
 * reactividade) e para relatórios PDF futuros.
 */
class HorarioAnalyser
{
    /**
     * Slots vazios entre dois slots ocupados no mesmo dia.
     * Cada item: ['dia' => int, 'tempo' => int]
     */
    public function furos(Turma $turma): Collection
    {
        $slots = $this->slotsPorDiaTempo($turma);
        $diasLectivos = config('escola.dias_lectivos', [1, 2, 3, 4, 5]);
        $tempos = array_keys(config('escola.tempos_lectivos'));
        sort($tempos);

        $out = collect();
        foreach ($diasLectivos as $dia) {
            $ocupados = collect($tempos)->filter(fn ($t) => isset($slots[$dia][$t]))->values();
            if ($ocupados->count() < 2) {
                continue;
            }
            $min = $ocupados->first();
            $max = $ocupados->last();
            foreach ($tempos as $t) {
                if ($t > $min && $t < $max && ! isset($slots[$dia][$t])) {
                    $out->push(['dia' => $dia, 'tempo' => $t]);
                }
            }
        }
        return $out;
    }

    /**
     * Atribuições da turma sem nenhum slot atribuído.
     */
    public function naoEscaladas(Turma $turma): Collection
    {
        $atrIdsEscalados = Horario::whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))
            ->pluck('atribuicao_id')
            ->unique();

        return Atribuicao::with(['disciplina', 'professor.user'])
            ->where('turma_id', $turma->id)
            ->whereNotIn('id', $atrIdsEscalados)
            ->get();
    }

    /**
     * Comparação carga horária esperada vs actual por atribuição.
     * Cada item: ['atribuicao' => Atribuicao, 'esperada' => int|null, 'actual' => int, 'diff' => int|null]
     * `esperada` é null se disciplina não declarar `carga_horaria_semanal`.
     */
    public function cargaHoraria(Turma $turma): Collection
    {
        $atribuicoes = Atribuicao::with('disciplina', 'professor.user')
            ->where('turma_id', $turma->id)
            ->get();

        $contagem = Horario::whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))
            ->selectRaw('atribuicao_id, count(*) as n')
            ->groupBy('atribuicao_id')
            ->pluck('n', 'atribuicao_id');

        return $atribuicoes->map(function ($a) use ($contagem) {
            $esperada = $a->disciplina->carga_horaria_semanal;
            $actual = (int) ($contagem[$a->id] ?? 0);
            return [
                'atribuicao' => $a,
                'esperada' => $esperada,
                'actual' => $actual,
                'diff' => $esperada !== null ? $actual - $esperada : null,
            ];
        });
    }

    /**
     * Disciplinas pesadas concentradas num só dia: ≥2 tempos da mesma
     * atribuição num único dia e essa fatia representa >50% do total semanal.
     * Cada item: ['atribuicao' => Atribuicao, 'dia' => int, 'count' => int, 'total' => int]
     */
    public function concentracaoDiaria(Turma $turma): Collection
    {
        $pesadas = collect(config('escola.disciplinas_pesadas', []))
            ->map(fn ($s) => strtoupper($s));

        $rows = Horario::whereHas('atribuicao.disciplina', fn ($q) => null)
            ->whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))
            ->with(['atribuicao.disciplina', 'atribuicao.professor.user'])
            ->get();

        $porAtrDia = [];
        foreach ($rows as $h) {
            $sigla = strtoupper((string) $h->atribuicao->disciplina->sigla);
            if (! $pesadas->contains($sigla)) continue;
            $aid = $h->atribuicao_id;
            $porAtrDia[$aid]['atribuicao'] = $h->atribuicao;
            $porAtrDia[$aid]['porDia'][$h->dia_semana] = ($porAtrDia[$aid]['porDia'][$h->dia_semana] ?? 0) + 1;
            $porAtrDia[$aid]['total'] = ($porAtrDia[$aid]['total'] ?? 0) + 1;
        }

        $out = collect();
        foreach ($porAtrDia as $info) {
            if ($info['total'] < 2) continue;
            foreach ($info['porDia'] as $dia => $n) {
                if ($n >= 2 && ($n / $info['total']) > 0.5) {
                    $out->push([
                        'atribuicao' => $info['atribuicao'],
                        'dia' => $dia,
                        'count' => $n,
                        'total' => $info['total'],
                    ]);
                }
            }
        }
        return $out;
    }

    /**
     * Runs de tempos consecutivos > config('escola.max_tempos_consecutivos')
     * para o mesmo professor num dia. Cada item:
     * ['professor' => Professor, 'dia' => int, 'run' => int, 'start' => int, 'end' => int]
     */
    public function tempasConsecutivos(Turma $turma): Collection
    {
        $limite = (int) config('escola.max_tempos_consecutivos', 3);
        $rows = Horario::whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))
            ->with('atribuicao.professor.user')
            ->orderBy('dia_semana')
            ->orderBy('tempo')
            ->get();

        // agrupa: profId → dia → [tempos]
        $matriz = [];
        $profs = [];
        foreach ($rows as $h) {
            $pid = $h->atribuicao->professor_id;
            $profs[$pid] = $h->atribuicao->professor;
            $matriz[$pid][$h->dia_semana][] = $h->tempo;
        }

        $out = collect();
        foreach ($matriz as $pid => $dias) {
            foreach ($dias as $dia => $temposArr) {
                sort($temposArr);
                $run = 1;
                $start = $temposArr[0];
                for ($i = 1; $i < count($temposArr); $i++) {
                    if ($temposArr[$i] === $temposArr[$i - 1] + 1) {
                        $run++;
                    } else {
                        if ($run > $limite) {
                            $out->push(['professor' => $profs[$pid], 'dia' => $dia, 'run' => $run, 'start' => $start, 'end' => $temposArr[$i - 1]]);
                        }
                        $run = 1;
                        $start = $temposArr[$i];
                    }
                }
                if ($run > $limite) {
                    $out->push(['professor' => $profs[$pid], 'dia' => $dia, 'run' => $run, 'start' => $start, 'end' => end($temposArr)]);
                }
            }
        }
        return $out;
    }

    /**
     * Disciplinas pesadas colocadas em slots configurados como "horas más"
     * em `config('escola.horas_dificeis')`. Cada item:
     * ['atribuicao' => Atribuicao, 'dia' => int, 'tempo' => int]
     */
    public function horasMas(Turma $turma): Collection
    {
        $horas = collect(config('escola.horas_dificeis', []));
        if ($horas->isEmpty()) return collect();

        $pesadas = collect(config('escola.disciplinas_pesadas', []))->map(fn ($s) => strtoupper($s));

        $rows = Horario::whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))
            ->with('atribuicao.disciplina')
            ->get();

        $out = collect();
        foreach ($rows as $h) {
            $sigla = strtoupper((string) $h->atribuicao->disciplina->sigla);
            if (! $pesadas->contains($sigla)) continue;
            $match = $horas->first(fn ($par) => $par[0] === $h->dia_semana && $par[1] === $h->tempo);
            if ($match) {
                $out->push(['atribuicao' => $h->atribuicao, 'dia' => $h->dia_semana, 'tempo' => $h->tempo]);
            }
        }
        return $out;
    }

    /**
     * Resumo agregado, pronto para o painel.
     */
    public function resumo(Turma $turma): array
    {
        $cargas = $this->cargaHoraria($turma);
        return [
            'furos' => $this->furos($turma)->count(),
            'nao_escaladas' => $this->naoEscaladas($turma)->count(),
            'carga_ok' => $cargas->filter(fn ($c) => $c['diff'] === 0)->count(),
            'carga_falta' => $cargas->filter(fn ($c) => $c['diff'] !== null && $c['diff'] < 0)->count(),
            'carga_excesso' => $cargas->filter(fn ($c) => $c['diff'] !== null && $c['diff'] > 0)->count(),
            'concentracao_diaria' => $this->concentracaoDiaria($turma)->count(),
            'tempas_consecutivos' => $this->tempasConsecutivos($turma)->count(),
            'horas_mas' => $this->horasMas($turma)->count(),
        ];
    }

    /** @return array<int, array<int, true>> */
    protected function slotsPorDiaTempo(Turma $turma): array
    {
        $rows = Horario::whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))
            ->get(['dia_semana', 'tempo']);
        $out = [];
        foreach ($rows as $r) {
            $out[$r->dia_semana][$r->tempo] = true;
        }
        return $out;
    }
}
