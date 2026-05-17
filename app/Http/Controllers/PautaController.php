<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\Atribuicao;
use App\Models\Avaliacao;
use App\Models\Matricula;
use App\Models\Trimestre;
use App\Models\Turma;
use App\Models\Encarregado;
use App\Services\Notifications\NotificationSender;
use App\Services\PautaCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PautaController extends Controller
{
    public function index(Request $request)
    {
        $anos = AnoLectivo::orderBy('codigo', 'desc')->get();
        $turmas = Turma::with(['classe', 'curso', 'anoLectivo'])->orderBy('classe_id')->orderBy('nome')->get();
        $trimestres = Trimestre::with('anoLectivo')->orderBy('numero')->get();

        $user = $request->user();
        $atribuicoes = Atribuicao::with(['turma.classe', 'disciplina', 'anoLectivo'])
            ->when($user->hasAnyRole(['professor', 'professor_assistente']), function ($q) use ($user) {
                if ($prof = $user->professor) $q->where('professor_id', $prof->id);
            })
            ->get();

        return view('pautas.index', compact('anos', 'turmas', 'trimestres', 'atribuicoes'));
    }

    /** MODO 1 — Pauta por disciplina + trimestre */
    public function disciplina(Request $request, Atribuicao $atribuicao, Trimestre $trimestre)
    {
        return view('pautas.disciplina', $this->disciplinaData($atribuicao, $trimestre));
    }

    /** MODO 2 — Pauta da turma no trimestre */
    public function turmaTrimestre(Request $request, Turma $turma, Trimestre $trimestre)
    {
        return view('pautas.turma-trimestre', $this->turmaTrimestreData($turma, $trimestre));
    }

    /** MODO 3 — Pauta anual da turma */
    public function turmaAnual(Request $request, Turma $turma)
    {
        return view('pautas.turma-anual', $this->turmaAnualData($turma, $this->pesosFromRequest($request)));
    }

    /** MODO 4 — Situação final da turma */
    public function situacao(Request $request, Turma $turma)
    {
        return view('pautas.situacao', $this->situacaoData($turma, $this->pesosFromRequest($request)));
    }

    // ----- PDF exports -----

    public function disciplinaPdf(Request $request, Atribuicao $atribuicao, Trimestre $trimestre)
    {
        $data = $this->disciplinaData($atribuicao, $trimestre);
        $atribuicao->load('professor.user');
        $data['atribuicao'] = $atribuicao;
        $filename = sprintf('pauta-%s-%s-%sT.pdf',
            str($atribuicao->turma->classe->nome . $atribuicao->turma->nome)->slug(),
            str($atribuicao->disciplina->nome)->slug(),
            $trimestre->numero
        );
        return Pdf::loadView('pdf.pautas.disciplina', $data)->setPaper('a4', 'portrait')->download($filename);
    }

    public function turmaTrimestrePdf(Request $request, Turma $turma, Trimestre $trimestre)
    {
        $data = $this->turmaTrimestreData($turma, $trimestre);
        $filename = sprintf('pauta-turma-%s-%s-%sT.pdf',
            str($turma->classe->nome . $turma->nome)->slug(),
            str($turma->anoLectivo->codigo)->slug(),
            $trimestre->numero
        );
        return Pdf::loadView('pdf.pautas.turma-trimestre', $data)->setPaper('a4', 'landscape')->download($filename);
    }

    public function turmaAnualPdf(Request $request, Turma $turma)
    {
        $pesos = $this->pesosFromRequest($request);
        $data = $this->turmaAnualData($turma, $pesos);
        $filename = sprintf('pauta-anual-%s-%s.pdf',
            str($turma->classe->nome . $turma->nome)->slug(),
            str($turma->anoLectivo->codigo)->slug()
        );
        return Pdf::loadView('pdf.pautas.turma-anual', $data)->setPaper('a4', 'landscape')->download($filename);
    }

    public function situacaoPdf(Request $request, Turma $turma)
    {
        $pesos = $this->pesosFromRequest($request);
        $data = $this->situacaoData($turma, $pesos);
        $filename = sprintf('resultados-%s-%s.pdf',
            str($turma->classe->nome . $turma->nome)->slug(),
            str($turma->anoLectivo->codigo)->slug()
        );
        return Pdf::loadView('pdf.pautas.situacao', $data)->setPaper('a4', 'portrait')->download($filename);
    }

    // ----- data builders (partilhados entre HTML e PDF) -----

    protected function disciplinaData(Atribuicao $atribuicao, Trimestre $trimestre): array
    {
        $atribuicao->load(['turma.classe', 'turma.curso', 'disciplina', 'anoLectivo']);

        $avaliacoes = Avaliacao::where('atribuicao_id', $atribuicao->id)
            ->where('trimestre_id', $trimestre->id)
            ->with('notas')->orderBy('data')->get();

        $matriculas = $this->matriculasDaTurma($atribuicao->turma_id, $atribuicao->ano_lectivo_id);

        $notasMap = [];
        foreach ($avaliacoes as $av) {
            foreach ($av->notas as $n) {
                $notasMap[$n->matricula_id][$av->id] = $n->valor;
            }
        }

        $calc = new PautaCalculator();
        $medias = [];
        foreach ($matriculas as $m) {
            $itens = [];
            foreach ($avaliacoes as $av) {
                $itens[] = ['valor' => $notasMap[$m->id][$av->id] ?? null, 'peso' => $av->peso];
            }
            $medias[$m->id] = $calc->mediaTrimestre($itens);
        }

        return compact('atribuicao', 'trimestre', 'avaliacoes', 'matriculas', 'notasMap', 'medias', 'calc');
    }

    protected function turmaTrimestreData(Turma $turma, Trimestre $trimestre): array
    {
        $turma->load(['classe', 'curso', 'anoLectivo', 'directorTurma.user']);

        $atribuicoes = Atribuicao::with('disciplina')
            ->where('turma_id', $turma->id)
            ->where('ano_lectivo_id', $turma->ano_lectivo_id)->get();

        $matriculas = $this->matriculasDaTurma($turma->id, $turma->ano_lectivo_id);
        $calc = new PautaCalculator();

        $todasAvaliacoes = Avaliacao::whereIn('atribuicao_id', $atribuicoes->pluck('id'))
            ->where('trimestre_id', $trimestre->id)
            ->with('notas')->get()->groupBy('atribuicao_id');

        $mediasTurma = [];
        foreach ($matriculas as $m) {
            foreach ($atribuicoes as $atr) {
                $avs = $todasAvaliacoes[$atr->id] ?? collect();
                $itens = $avs->map(fn ($av) => [
                    'valor' => $av->notas->firstWhere('matricula_id', $m->id)?->valor,
                    'peso' => $av->peso,
                ])->all();
                $mediasTurma[$m->id][$atr->disciplina_id] = $calc->mediaTrimestre($itens);
            }
        }

        $mediaGeral = [];
        foreach ($matriculas as $m) {
            $mediaGeral[$m->id] = $calc->mediaGeral($mediasTurma[$m->id] ?? []);
        }

        return compact('turma', 'trimestre', 'atribuicoes', 'matriculas', 'mediasTurma', 'mediaGeral', 'calc');
    }

    protected function turmaAnualData(Turma $turma, array $pesos): array
    {
        $calc = new PautaCalculator($pesos);
        $turma->load(['classe', 'curso', 'anoLectivo', 'directorTurma.user']);

        $atribuicoes = Atribuicao::with('disciplina')
            ->where('turma_id', $turma->id)
            ->where('ano_lectivo_id', $turma->ano_lectivo_id)->get();

        $trimestres = Trimestre::where('ano_lectivo_id', $turma->ano_lectivo_id)->orderBy('numero')->get();
        $matriculas = $this->matriculasDaTurma($turma->id, $turma->ano_lectivo_id);

        $todasAvaliacoes = Avaliacao::whereIn('atribuicao_id', $atribuicoes->pluck('id'))
            ->with('notas')->get()->groupBy(['atribuicao_id', 'trimestre_id']);

        $mediasAnuais = [];
        $mediasPorTrimestre = [];

        foreach ($matriculas as $m) {
            foreach ($atribuicoes as $atr) {
                $mediasTrim = [];
                foreach ($trimestres as $t) {
                    $avs = $todasAvaliacoes[$atr->id][$t->id] ?? collect();
                    $itens = $avs->map(fn ($av) => [
                        'valor' => $av->notas->firstWhere('matricula_id', $m->id)?->valor,
                        'peso' => $av->peso,
                    ])->all();
                    $mediasTrim[$t->numero] = $calc->mediaTrimestre($itens);
                    $mediasPorTrimestre[$m->id][$atr->disciplina_id][$t->numero] = $mediasTrim[$t->numero];
                }
                $mediasAnuais[$m->id][$atr->disciplina_id] = $calc->mediaAnual($mediasTrim);
            }
        }

        $mediaGeral = [];
        $situacao = [];
        foreach ($matriculas as $m) {
            $mediaGeral[$m->id] = $calc->mediaGeral($mediasAnuais[$m->id] ?? []);
            $situacao[$m->id] = $calc->situacao($mediasAnuais[$m->id] ?? []);
        }

        return compact('turma', 'atribuicoes', 'trimestres', 'matriculas',
            'mediasAnuais', 'mediasPorTrimestre', 'mediaGeral', 'situacao', 'calc');
    }

    protected function situacaoData(Turma $turma, array $pesos): array
    {
        $data = $this->turmaAnualData($turma, $pesos);
        $calc = $data['calc'];

        $resumo = [];
        foreach ($data['matriculas'] as $m) {
            $medias = $data['mediasAnuais'][$m->id] ?? [];
            $negativas = [];
            foreach ($data['atribuicoes'] as $atr) {
                $v = $medias[$atr->disciplina_id] ?? null;
                if ($v !== null && $v < $calc->notaMinima) {
                    $negativas[] = $atr->disciplina->nome;
                }
            }
            $resumo[$m->id] = [
                'media_geral' => $data['mediaGeral'][$m->id] ?? null,
                'situacao' => $data['situacao'][$m->id] ?? 'em_curso',
                'negativas' => $negativas,
            ];
        }

        $agrupado = ['aprovado' => [], 'recurso' => [], 'reprovado' => [], 'em_curso' => []];
        foreach ($data['matriculas'] as $m) {
            $agrupado[$resumo[$m->id]['situacao']][] = $m;
        }

        return [
            'turma' => $data['turma'],
            'matriculas' => $data['matriculas'],
            'resumo' => $resumo,
            'agrupado' => $agrupado,
            'calc' => $calc,
        ];
    }

    /**
     * Notifica os encarregados dos alunos da turma de que o boletim do
     * trimestre está disponível. Dispara o evento 'boletim_fechado'.
     */
    public function notificarBoletim(Request $request, Turma $turma, Trimestre $trimestre)
    {
        $turma->load('classe');
        $matriculas = $this->matriculasDaTurma($turma->id, $turma->ano_lectivo_id);
        $sender = app(NotificationSender::class);
        $totalEnviado = 0;

        foreach ($matriculas as $m) {
            $encarregadoUsers = Encarregado::query()
                ->whereHas('alunos', fn ($q) => $q->whereKey($m->aluno_id))
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter();

            if ($encarregadoUsers->isEmpty()) continue;

            $result = $sender->dispatch(
                eventKey: 'boletim_fechado',
                recipients: $encarregadoUsers,
                channels: ['email'],
                payload: [
                    'aluno' => $m->aluno->user->name ?? '—',
                    'trimestre' => $trimestre->numero,
                    'turma' => $turma->classe->nome.' '.$turma->nome,
                ],
            );
            $totalEnviado += $result['sent'] ?? 0;
        }

        return back()->with('status', __(':n notification(s) sent to guardians.', ['n' => $totalEnviado]));
    }

    // ----- helpers -----

    protected function matriculasDaTurma(int $turmaId, int $anoId)
    {
        return Matricula::with('aluno.user')
            ->where('turma_id', $turmaId)
            ->where('ano_lectivo_id', $anoId)
            ->whereIn('estado', ['activa', 'aprovado', 'reprovado', 'transferido'])
            ->get()
            ->sortBy(fn ($m) => $m->aluno->user->name);
    }

    protected function pesosFromRequest(Request $request): array
    {
        $defaults = config('escola.pesos_trimestres', [1, 1, 1]);
        return [
            max(1, min(5, (int) $request->query('peso_t1', $defaults[0]))),
            max(1, min(5, (int) $request->query('peso_t2', $defaults[1]))),
            max(1, min(5, (int) $request->query('peso_t3', $defaults[2]))),
        ];
    }
}
