<?php

namespace App\Http\Controllers;

use App\Models\FaltaProfessor;
use App\Models\Professor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FaltaProfessorController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', FaltaProfessor::class);

        $user = $request->user();
        $mes = $request->query('mes');           // YYYY-MM
        $profId = $request->query('professor_id');

        $faltas = FaltaProfessor::with(['professor.user', 'substituto.user', 'registadoPor'])
            ->when($user->hasAnyRole(['professor', 'professor_assistente']), function ($q) use ($user) {
                $pid = $user->professor?->id;
                $q->where(function ($w) use ($pid) {
                    $w->where('professor_id', $pid)
                      ->orWhere('substituto_id', $pid);
                });
            })
            ->when($profId && ! $user->hasAnyRole(['professor', 'professor_assistente']), fn ($q) => $q->where('professor_id', $profId))
            ->when($mes, function ($q) use ($mes) {
                [$y, $m] = explode('-', $mes);
                $q->whereYear('data', $y)->whereMonth('data', $m);
            })
            ->orderBy('data', 'desc')
            ->orderBy('tempo_inicio')
            ->paginate(25)
            ->withQueryString();

        $professores = $user->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario'])
            ? Professor::with('user')->orderBy('id')->get()
            : collect();

        return view('faltas-professores.index', compact('faltas', 'professores', 'mes', 'profId'));
    }

    public function create()
    {
        $this->authorize('create', FaltaProfessor::class);

        return view('faltas-professores.create', [
            'professores' => Professor::with('user')->orderBy('id')->get(),
            'tempos' => config('escola.tempos_lectivos'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', FaltaProfessor::class);

        $data = $this->validateData($request);
        $data['registado_por_id'] = $request->user()->id;

        if ($data['tipo'] === 'justificada' && $request->boolean('ja_justificada')) {
            $data['justificacao_em'] = now();
        }

        FaltaProfessor::create($data);

        return redirect()->route('faltas-professores.index')
            ->with('status', __('Resource created successfully.'));
    }

    public function show(FaltaProfessor $falta)
    {
        $this->authorize('view', $falta);
        $falta->load(['professor.user', 'substituto.user', 'registadoPor']);
        return view('faltas-professores.show', compact('falta'));
    }

    public function edit(FaltaProfessor $falta)
    {
        $this->authorize('update', $falta);
        return view('faltas-professores.edit', [
            'falta' => $falta,
            'professores' => Professor::with('user')->orderBy('id')->get(),
            'tempos' => config('escola.tempos_lectivos'),
        ]);
    }

    public function update(Request $request, FaltaProfessor $falta)
    {
        $this->authorize('update', $falta);
        $data = $this->validateData($request, $falta);
        $falta->update($data);

        return redirect()->route('faltas-professores.index')
            ->with('status', __('Resource updated successfully.'));
    }

    public function destroy(FaltaProfessor $falta)
    {
        $this->authorize('delete', $falta);
        $falta->delete();

        return redirect()->route('faltas-professores.index')
            ->with('status', __('Resource deleted successfully.'));
    }

    /** Marca uma falta como justificada (carimba a data). */
    public function justify(Request $request, FaltaProfessor $falta)
    {
        $this->authorize('justify', $falta);

        $falta->update([
            'tipo' => 'justificada',
            'justificacao_em' => now(),
        ]);

        return redirect()->back()
            ->with('status', __('Absence marked as justified.'));
    }

    protected function validateData(Request $request, ?FaltaProfessor $falta = null): array
    {
        return $request->validate([
            'professor_id' => ['required', Rule::exists('professores', 'id')],
            'data' => ['required', 'date'],
            'tempo_inicio' => ['required', 'integer', Rule::in(array_keys(config('escola.tempos_lectivos')))],
            'tempo_fim' => ['required', 'integer', 'gte:tempo_inicio', Rule::in(array_keys(config('escola.tempos_lectivos')))],
            'tipo' => ['required', Rule::in(['justificada', 'injustificada', 'licenca'])],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'substituto_id' => ['nullable', 'different:professor_id', Rule::exists('professores', 'id')],
        ]);
    }
}
