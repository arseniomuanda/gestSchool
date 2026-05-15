<?php

namespace App\Http\Controllers;

use App\Ai\Agents\HorarioSugestor;
use App\Models\AnoLectivo;
use App\Models\Atribuicao;
use App\Models\Horario;
use App\Models\Professor;
use App\Models\Turma;
use App\Services\HorarioGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HorarioController extends Controller
{
    public function index(Request $request)
    {
        $turmas = Turma::with(['classe', 'curso', 'anoLectivo'])
            ->orderBy('classe_id')->orderBy('nome')->get();

        $user = $request->user();
        $professores = Professor::with('user')
            ->when($user->hasAnyRole(['professor', 'professor_assistente']), function ($q) use ($user) {
                if ($prof = $user->professor) $q->where('id', $prof->id);
            })
            ->orderBy('id')->get();

        return view('horarios.index', compact('turmas', 'professores'));
    }

    public function turma(Request $request, Turma $turma)
    {
        $turma->load(['classe', 'curso', 'anoLectivo']);

        $horarios = Horario::whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))
            ->with(['atribuicao.disciplina', 'atribuicao.professor.user'])
            ->get()
            ->groupBy(fn ($h) => $h->dia_semana . '-' . $h->tempo);

        return view('horarios.turma', compact('turma', 'horarios'));
    }

    public function professor(Request $request, Professor $professor)
    {
        $professor->load('user');

        $horarios = Horario::whereHas('atribuicao', fn ($q) => $q->where('professor_id', $professor->id))
            ->with(['atribuicao.turma.classe', 'atribuicao.turma.curso', 'atribuicao.disciplina'])
            ->get()
            ->groupBy(fn ($h) => $h->dia_semana . '-' . $h->tempo);

        return view('horarios.professor', compact('professor', 'horarios'));
    }

    public function create(Request $request)
    {
        return view('horarios.create', $this->options($request));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $this->checkConflitos($data);
        Horario::create($data);
        return redirect()->route('horarios.index')->with('status', __('Resource created successfully.'));
    }

    public function edit(Request $request, Horario $horario)
    {
        $horario->load('atribuicao');
        return view('horarios.edit', array_merge(['horario' => $horario], $this->options($request)));
    }

    public function update(Request $request, Horario $horario)
    {
        $data = $this->validateData($request, $horario);
        $this->checkConflitos($data, $horario->id);
        $horario->update($data);
        return redirect()->route('horarios.index')->with('status', __('Resource updated successfully.'));
    }

    public function destroy(Horario $horario)
    {
        $horario->delete();
        return redirect()->route('horarios.index')->with('status', __('Resource deleted successfully.'));
    }

    /**
     * Grelha de bulk edit do horário de uma turma — preenchimento da semana inteira.
     */
    public function bulkTurma(Request $request, Turma $turma)
    {
        $turma->load(['classe', 'curso', 'anoLectivo']);

        $atribuicoes = Atribuicao::with(['disciplina', 'professor.user'])
            ->where('turma_id', $turma->id)
            ->where('ano_lectivo_id', $turma->ano_lectivo_id)
            ->get()
            ->sortBy('disciplina.nome');

        $horariosActuais = Horario::whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))
            ->get()
            ->keyBy(fn ($h) => $h->dia_semana . '-' . $h->tempo);

        $tempos = config('escola.tempos_lectivos');
        $diasLectivos = config('escola.dias_lectivos', [1, 2, 3, 4, 5]);

        // Pré-inicializa todos os slots para que o Alpine.x-model tenha estrutura completa
        $initialSlots = [];
        foreach ($diasLectivos as $d) {
            foreach (array_keys($tempos) as $t) {
                $h = $horariosActuais->get($d . '-' . $t);
                $initialSlots[$d][$t] = [
                    'atribuicao_id' => $h?->atribuicao_id ? (string) $h->atribuicao_id : '',
                    'sala' => $h?->sala ?? '',
                ];
            }
        }

        return view('horarios.bulk-turma', [
            'turma' => $turma,
            'atribuicoes' => $atribuicoes,
            'initialSlots' => $initialSlots,
            'tempos' => $tempos,
            'diasSemana' => Horario::diasSemana(),
            'diasLectivos' => $diasLectivos,
        ]);
    }

    public function bulkTurmaStore(Request $request, Turma $turma)
    {
        $slots = $request->input('slots', []);   // [dia][tempo] => ['atribuicao_id' => X, 'sala' => Y]

        $validIds = Atribuicao::where('turma_id', $turma->id)
            ->where('ano_lectivo_id', $turma->ano_lectivo_id)
            ->pluck('id')->all();

        // ---- validação em lote (não escreve nada até confirmar tudo OK) ----
        $aGravar = [];           // linhas a inserir
        $vistosProf = [];        // [dia-tempo] => professor_id (para detectar choque entre slots do próprio submit)
        $vistosTurma = [];       // não há choque dentro da turma porque seleccionamos 1 atribuicao por (dia, tempo)

        foreach ($slots as $dia => $temposArr) {
            $dia = (int) $dia;
            if (! in_array($dia, [1,2,3,4,5,6,7], true)) continue;

            foreach ($temposArr as $tempo => $info) {
                $tempo = (int) $tempo;
                if (! array_key_exists($tempo, config('escola.tempos_lectivos'))) continue;

                $atrId = $info['atribuicao_id'] ?? null;
                if (! $atrId) continue;
                if (! in_array((int) $atrId, $validIds, true)) {
                    return back()->withErrors(['slots' => sprintf(__('Invalid assignment for this class in slot %d/%dº.'), $dia, $tempo)])->withInput();
                }

                $atribuicao = Atribuicao::find($atrId);
                $key = "{$dia}-{$tempo}";

                if (isset($vistosProf[$key]) && $vistosProf[$key] !== $atribuicao->professor_id) {
                    // Não deveria acontecer, pois fixmos 1 célula por slot
                }
                $vistosProf[$key] = $atribuicao->professor_id;

                // Conflito com horários de OUTRAS turmas (mesmo professor)
                $conflitoExterno = Horario::where('dia_semana', $dia)
                    ->where('tempo', $tempo)
                    ->whereHas('atribuicao', function ($q) use ($atribuicao, $turma) {
                        $q->where('professor_id', $atribuicao->professor_id)
                          ->where('turma_id', '!=', $turma->id);
                    })
                    ->with(['atribuicao.turma.classe', 'atribuicao.disciplina'])
                    ->first();

                if ($conflitoExterno) {
                    return back()->withErrors([
                        'slots' => sprintf(
                            __('Schedule conflict: %s already teaches %s in class %s (day %s, period %d).'),
                            $atribuicao->professor->user->name,
                            $conflitoExterno->atribuicao->disciplina->nome,
                            $conflitoExterno->atribuicao->turma->nome_completo,
                            $dia,
                            $tempo
                        ),
                    ])->withInput();
                }

                $aGravar[] = [
                    'atribuicao_id' => $atrId,
                    'dia_semana' => $dia,
                    'tempo' => $tempo,
                    'sala' => $info['sala'] ?: null,
                ];
            }
        }

        // ---- gravação atómica ----
        DB::transaction(function () use ($turma, $aGravar) {
            // Apagar horários antigos desta turma (clean slate)
            Horario::whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))->delete();

            // Inserir novos
            foreach ($aGravar as $row) {
                Horario::create($row);
            }
        });

        return redirect()->route('horarios.turma', $turma)
            ->with('status', sprintf(__('%d slots saved.'), count($aGravar)));
    }

    /**
     * Devolve JSON com uma proposta de horário (greedy) para a turma.
     * O frontend Alpine popula o state sem gravar; user revê e submete o form normal.
     */
    public function autoGenerate(Request $request, Turma $turma, HorarioGenerator $generator): JsonResponse
    {
        $grelha = $generator->propor($turma);
        return response()->json([
            'slots' => $grelha,
            'unplaced' => count($generator->unplaced),
            'method' => 'greedy',
        ]);
    }

    /**
     * Devolve JSON com uma proposta de horário pedida ao Gemini.
     * Fallback gracioso se a chave não estiver definida ou o agent falhar.
     */
    public function autoGenerateAi(Request $request, Turma $turma): JsonResponse
    {
        if (! config('ai.providers.gemini.key')) {
            return response()->json(['error' => __('AI provider is not configured.')], 503);
        }

        $atribuicoes = Atribuicao::with('disciplina', 'professor.user')
            ->where('turma_id', $turma->id)
            ->where('ano_lectivo_id', $turma->ano_lectivo_id)
            ->get();

        $diasLectivos = config('escola.dias_lectivos', [1, 2, 3, 4, 5]);
        $tempos = array_keys(config('escola.tempos_lectivos'));
        sort($tempos);
        $pesadas = collect(config('escola.disciplinas_pesadas', []))->map(fn ($s) => strtoupper($s));

        $conflitos = Horario::whereHas('atribuicao', fn ($q) => $q
                ->where('turma_id', '!=', $turma->id)
                ->where('ano_lectivo_id', $turma->ano_lectivo_id))
            ->with('atribuicao')
            ->get()
            ->map(fn ($h) => "dia={$h->dia_semana}, tempo={$h->tempo}, professor_id={$h->atribuicao->professor_id}")
            ->implode("\n");

        $atrLines = $atribuicoes->map(function ($a) use ($pesadas) {
            $sigla = strtoupper((string) $a->disciplina->sigla);
            $pesada = $pesadas->contains($sigla) ? 'sim' : 'não';
            return "- id={$a->id}, disciplina={$a->disciplina->nome} ({$sigla}), professor_id={$a->professor_id}, carga={$a->disciplina->carga_horaria_semanal}, pesada={$pesada}";
        })->implode("\n");

        $prompt = <<<PROMPT
Turma: {$turma->classe->nome}{$turma->nome}
Dias lectivos: {$this->formatList($diasLectivos)}
Tempos por dia: {$this->formatList($tempos)}

Atribuições disponíveis (id, disciplina, professor, carga horária semanal, é pesada?):
{$atrLines}

Conflitos a evitar (professores já ocupados noutras turmas neste slot):
{$conflitos}

Devolve um array `slots` com a distribuição que respeita as regras.
PROMPT;

        try {
            $response = (new HorarioSugestor())->prompt($prompt);
            $slotsRaw = $response['slots'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('HorarioSugestor falhou', ['error' => $e->getMessage(), 'turma' => $turma->id]);
            return response()->json(['error' => __('AI suggestion failed.'), 'detail' => $e->getMessage()], 502);
        }

        // Inicializa grelha vazia e aplica só atribuições válidas
        $validIds = $atribuicoes->pluck('id')->all();
        $grelha = [];
        foreach ($diasLectivos as $d) {
            foreach ($tempos as $t) {
                $grelha[$d][$t] = ['atribuicao_id' => '', 'sala' => ''];
            }
        }
        $applied = 0;
        $rejected = 0;
        foreach ($slotsRaw as $s) {
            $dia = (int) ($s['dia'] ?? 0);
            $tempo = (int) ($s['tempo'] ?? 0);
            $aid = (int) ($s['atribuicao_id'] ?? 0);
            if (! in_array($dia, $diasLectivos, true)) { $rejected++; continue; }
            if (! in_array($tempo, $tempos, true)) { $rejected++; continue; }
            if (! in_array($aid, $validIds, true)) { $rejected++; continue; }
            if ($grelha[$dia][$tempo]['atribuicao_id'] !== '') { $rejected++; continue; }
            $grelha[$dia][$tempo]['atribuicao_id'] = (string) $aid;
            $applied++;
        }

        return response()->json([
            'slots' => $grelha,
            'applied' => $applied,
            'rejected' => $rejected,
            'method' => 'gemini',
        ]);
    }

    protected function formatList(array $arr): string
    {
        return implode(', ', $arr);
    }

    /**
     * Grelha de bulk edit do horário de um professor — todas as suas atribuições do ano activo.
     */
    public function bulkProfessor(Request $request, Professor $professor)
    {
        $professor->load('user');

        $anoActivo = AnoLectivo::activo();

        $atribuicoes = Atribuicao::with(['disciplina', 'turma.classe', 'turma.curso'])
            ->where('professor_id', $professor->id)
            ->when($anoActivo, fn ($q) => $q->where('ano_lectivo_id', $anoActivo->id))
            ->get()
            ->sortBy(fn ($a) => $a->turma->classe->nome . $a->turma->nome . $a->disciplina->nome);

        $horariosActuais = Horario::whereHas('atribuicao', fn ($q) => $q->where('professor_id', $professor->id)
                ->when($anoActivo, fn ($qq) => $qq->where('ano_lectivo_id', $anoActivo->id)))
            ->get()
            ->keyBy(fn ($h) => $h->dia_semana . '-' . $h->tempo);

        $tempos = config('escola.tempos_lectivos');
        $diasLectivos = config('escola.dias_lectivos', [1, 2, 3, 4, 5]);

        $initialSlots = [];
        foreach ($diasLectivos as $d) {
            foreach (array_keys($tempos) as $t) {
                $h = $horariosActuais->get($d . '-' . $t);
                $initialSlots[$d][$t] = [
                    'atribuicao_id' => $h?->atribuicao_id ? (string) $h->atribuicao_id : '',
                    'sala' => $h?->sala ?? '',
                ];
            }
        }

        return view('horarios.bulk-professor', [
            'professor' => $professor,
            'atribuicoes' => $atribuicoes,
            'initialSlots' => $initialSlots,
            'tempos' => $tempos,
            'diasSemana' => Horario::diasSemana(),
            'diasLectivos' => $diasLectivos,
            'anoActivo' => $anoActivo,
        ]);
    }

    public function bulkProfessorStore(Request $request, Professor $professor)
    {
        $slots = $request->input('slots', []);

        $anoActivo = AnoLectivo::activo();

        $validIds = Atribuicao::where('professor_id', $professor->id)
            ->when($anoActivo, fn ($q) => $q->where('ano_lectivo_id', $anoActivo->id))
            ->pluck('id')->all();

        $aGravar = [];

        foreach ($slots as $dia => $temposArr) {
            $dia = (int) $dia;
            if (! in_array($dia, [1,2,3,4,5,6,7], true)) continue;

            foreach ($temposArr as $tempo => $info) {
                $tempo = (int) $tempo;
                if (! array_key_exists($tempo, config('escola.tempos_lectivos'))) continue;

                $atrId = $info['atribuicao_id'] ?? null;
                if (! $atrId) continue;
                if (! in_array((int) $atrId, $validIds, true)) {
                    return back()->withErrors(['slots' => sprintf(__('Invalid assignment for this teacher in slot %d/%dº.'), $dia, $tempo)])->withInput();
                }

                $atribuicao = Atribuicao::find($atrId);

                // Conflito por TURMA: a turma alvo já tem aula nesse slot (de outro professor)?
                $conflitoTurma = Horario::where('dia_semana', $dia)
                    ->where('tempo', $tempo)
                    ->whereHas('atribuicao', function ($q) use ($atribuicao, $professor) {
                        $q->where('turma_id', $atribuicao->turma_id)
                          ->where('professor_id', '!=', $professor->id);
                    })
                    ->with(['atribuicao.disciplina', 'atribuicao.professor.user', 'atribuicao.turma.classe'])
                    ->first();

                if ($conflitoTurma) {
                    return back()->withErrors([
                        'slots' => sprintf(
                            __('Schedule conflict: class %s already has %s with %s (day %s, period %d).'),
                            $conflitoTurma->atribuicao->turma->nome_completo,
                            $conflitoTurma->atribuicao->disciplina->nome,
                            $conflitoTurma->atribuicao->professor->user->name,
                            $dia,
                            $tempo
                        ),
                    ])->withInput();
                }

                $aGravar[] = [
                    'atribuicao_id' => $atrId,
                    'dia_semana' => $dia,
                    'tempo' => $tempo,
                    'sala' => $info['sala'] ?: null,
                ];
            }
        }

        DB::transaction(function () use ($professor, $anoActivo, $aGravar) {
            // Clean slate: apaga só horários DESTE professor (no ano activo)
            Horario::whereHas('atribuicao', fn ($q) => $q->where('professor_id', $professor->id)
                    ->when($anoActivo, fn ($qq) => $qq->where('ano_lectivo_id', $anoActivo->id)))
                ->delete();

            foreach ($aGravar as $row) {
                Horario::create($row);
            }
        });

        return redirect()->route('horarios.professor', $professor)
            ->with('status', sprintf(__('%d slots saved.'), count($aGravar)));
    }

    public function turmaPdf(Request $request, Turma $turma)
    {
        $turma->load(['classe', 'curso', 'anoLectivo']);
        $horarios = Horario::whereHas('atribuicao', fn ($q) => $q->where('turma_id', $turma->id))
            ->with(['atribuicao.disciplina', 'atribuicao.professor.user'])
            ->get()
            ->groupBy(fn ($h) => $h->dia_semana . '-' . $h->tempo);

        $filename = sprintf('horario-%s-%s.pdf',
            str($turma->classe->nome . $turma->nome)->slug(),
            str($turma->anoLectivo->codigo)->slug()
        );

        return Pdf::loadView('pdf.horarios.turma', [
            'turma' => $turma,
            'horarios' => $horarios,
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    public function professorPdf(Request $request, Professor $professor)
    {
        $professor->load('user');
        $horarios = Horario::whereHas('atribuicao', fn ($q) => $q->where('professor_id', $professor->id))
            ->with(['atribuicao.turma.classe', 'atribuicao.turma.curso', 'atribuicao.disciplina'])
            ->get()
            ->groupBy(fn ($h) => $h->dia_semana . '-' . $h->tempo);

        $filename = sprintf('horario-prof-%s.pdf', str($professor->user->name)->slug());

        return Pdf::loadView('pdf.horarios.professor', [
            'professor' => $professor,
            'horarios' => $horarios,
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    // ----- helpers -----

    protected function options(Request $request): array
    {
        $user = $request->user();
        $atribuicoes = Atribuicao::with(['turma.classe', 'turma.curso', 'disciplina', 'professor.user', 'anoLectivo'])
            ->when($user->hasAnyRole(['professor', 'professor_assistente']), function ($q) use ($user) {
                if ($prof = $user->professor) $q->where('professor_id', $prof->id);
            })
            ->get();

        return [
            'atribuicoes' => $atribuicoes,
            'tempos' => config('escola.tempos_lectivos'),
            'diasSemana' => Horario::diasSemana(),
        ];
    }

    protected function validateData(Request $request, ?Horario $horario = null): array
    {
        return $request->validate([
            'atribuicao_id' => ['required', Rule::exists('atribuicoes', 'id')],
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'tempo' => ['required', 'integer', Rule::in(array_keys(config('escola.tempos_lectivos')))],
            'sala' => ['nullable', 'string', 'max:30'],
            'observacao' => ['nullable', 'string', 'max:200'],
        ]);
    }

    protected function checkConflitos(array $data, ?int $excludeId = null): void
    {
        $atribuicao = Atribuicao::with('professor', 'turma')->find($data['atribuicao_id']);
        if (! $atribuicao) return;

        // Professor já tem aula nesse slot?
        $conflitoProf = Horario::where('dia_semana', $data['dia_semana'])
            ->where('tempo', $data['tempo'])
            ->whereHas('atribuicao', fn ($q) => $q->where('professor_id', $atribuicao->professor_id))
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->with('atribuicao.disciplina', 'atribuicao.turma.classe')
            ->first();

        if ($conflitoProf) {
            throw ValidationException::withMessages([
                'atribuicao_id' => sprintf(
                    __('Teacher already has a lesson in this slot: %s in class %s.'),
                    $conflitoProf->atribuicao->disciplina->nome,
                    $conflitoProf->atribuicao->turma->nome_completo
                ),
            ]);
        }

        // Turma já tem aula nesse slot?
        $conflitoTurma = Horario::where('dia_semana', $data['dia_semana'])
            ->where('tempo', $data['tempo'])
            ->whereHas('atribuicao', fn ($q) => $q->where('turma_id', $atribuicao->turma_id))
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->with('atribuicao.disciplina')
            ->first();

        if ($conflitoTurma) {
            throw ValidationException::withMessages([
                'atribuicao_id' => sprintf(
                    __('Class already has a lesson in this slot: %s.'),
                    $conflitoTurma->atribuicao->disciplina->nome
                ),
            ]);
        }
    }
}
