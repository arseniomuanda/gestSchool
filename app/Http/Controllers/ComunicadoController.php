<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Comunicado;
use App\Models\Turma;
use App\Services\Notifications\NotificationSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ComunicadoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $podeGerir = $user->can('comunicados.manage');

        $query = Comunicado::with('autor')->orderBy('publicado_em', 'desc')->orderBy('created_at', 'desc');

        if (! $podeGerir) {
            $query->publicados()->visivelPara($user);
        }

        $comunicados = $query->paginate(15);
        return view('comunicados.index', compact('comunicados', 'podeGerir'));
    }

    public function create()
    {
        return view('comunicados.create', $this->options());
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['autor_id'] = $request->user()->id;
        $comunicado = Comunicado::create($data);
        $this->notifyIfPublished($comunicado);
        return redirect()->route('comunicados.index')->with('status', __('Resource created successfully.'));
    }

    public function show(Comunicado $comunicado)
    {
        $comunicado->load(['autor', 'classe', 'turma']);
        return view('comunicados.show', compact('comunicado'));
    }

    public function edit(Comunicado $comunicado)
    {
        return view('comunicados.edit', array_merge(['comunicado' => $comunicado], $this->options()));
    }

    public function update(Request $request, Comunicado $comunicado)
    {
        $wasPublished = (bool) $comunicado->publicado_em;
        $comunicado->update($this->validateData($request, $comunicado));
        // Só notifica quando passa de rascunho para publicado
        if (! $wasPublished) {
            $this->notifyIfPublished($comunicado->fresh());
        }
        return redirect()->route('comunicados.index')->with('status', __('Resource updated successfully.'));
    }

    public function destroy(Comunicado $comunicado)
    {
        $comunicado->delete();
        return redirect()->route('comunicados.index')->with('status', __('Resource deleted successfully.'));
    }

    /**
     * Dispara notificação por email aos destinatários quando o comunicado
     * foi efectivamente publicado (publicado_em <= now). Silenciosamente
     * não bloqueia o request em caso de falha — fica no log do módulo.
     */
    protected function notifyIfPublished(Comunicado $comunicado): void
    {
        if (! $comunicado->publicado_em || $comunicado->publicado_em->isFuture()) {
            return;
        }
        try {
            $destinatarios = $comunicado->destinatariosUsers();
            if ($destinatarios->isEmpty()) {
                return;
            }
            app(NotificationSender::class)->dispatch(
                eventKey: 'comunicado_publicado',
                recipients: $destinatarios,
                channels: ['email'],
                payload: [
                    'titulo' => $comunicado->titulo,
                    'mensagem' => strip_tags($comunicado->conteudo),
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('[comunicado_publicado] Falha no envio de notificações: '.$e->getMessage());
        }
    }

    protected function options(): array
    {
        return [
            'classes' => Classe::orderBy('ordem')->get(),
            'turmas' => Turma::with(['classe', 'anoLectivo'])->get(),
        ];
    }

    protected function validateData(Request $request, ?Comunicado $c = null): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'conteudo' => ['required', 'string'],
            'alcance' => ['required', Rule::in(['todos', 'professores', 'encarregados', 'classe', 'turma'])],
            'classe_id' => ['nullable', Rule::exists('classes', 'id')],
            'turma_id' => ['nullable', Rule::exists('turmas', 'id')],
            'publicado_em' => ['nullable', 'date'],
        ]);
    }
}
