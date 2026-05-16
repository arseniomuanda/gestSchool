<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use App\Models\Encarregado;
use App\Models\User;
use App\Services\BoletimService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AlunoController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $alunos = Aluno::with('user')
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('numero_processo', 'ilike', "%$q%")
                    ->orWhere('turma', 'ilike', "%$q%")
                    ->orWhere('classe', 'ilike', "%$q%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', "%$q%"));
            }))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('alunos.index', compact('alunos', 'q'));
    }

    public function create()
    {
        return view('alunos.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password'] ?? str()->random(12)),
            'is_active' => true,
        ]);

        $aluno = Aluno::create([
            'user_id' => $user->id,
            'numero_processo' => $data['numero_processo'],
            'bi' => $data['bi'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'classe' => $data['classe'] ?? null,
            'turma' => $data['turma'] ?? null,
            'ano_lectivo' => $data['ano_lectivo'] ?? null,
            'nacionalidade' => $data['nacionalidade'] ?? 'Angolana',
            'naturalidade' => $data['naturalidade'] ?? null,
            'morada' => $data['morada'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
        ]);

        if (! empty($data['encarregados'])) {
            $sync = [];
            foreach ($data['encarregados'] as $enc) {
                $sync[$enc['id']] = [
                    'parentesco' => $enc['parentesco'] ?? 'outro',
                    'principal' => ! empty($enc['principal']),
                ];
            }
            $aluno->encarregados()->sync($sync);
        }

        return redirect()->route('alunos.index')->with('status', __('Resource created successfully.'));
    }

    public function show(Aluno $aluno, BoletimService $boletimService)
    {
        $aluno->load([
            'user',
            'encarregados.user',
            'matriculas' => fn ($q) => $q->orderByDesc('ano_lectivo_id'),
            'matriculas.turma.classe',
            'matriculas.turma.curso',
            'matriculas.turma.directorTurma.user',
            'matriculas.anoLectivo',
        ]);

        $resumos = [];
        foreach ($aluno->matriculas as $m) {
            $resumos[$m->id] = $boletimService->quickSummary($m);
        }

        $matriculaActiva = $aluno->matriculas->firstWhere('estado', 'activa');
        $boletim = $matriculaActiva ? $boletimService->build($matriculaActiva) : null;

        return view('alunos.show', compact('aluno', 'resumos', 'matriculaActiva', 'boletim'));
    }

    public function edit(Aluno $aluno)
    {
        $aluno->load(['user', 'encarregados.user']);
        return view('alunos.edit', compact('aluno'));
    }

    public function update(Request $request, Aluno $aluno)
    {
        $data = $this->validateData($request, $aluno);

        $aluno->user->fill([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $aluno->user->password = Hash::make($data['password']);
        }
        $aluno->user->save();

        $aluno->update([
            'numero_processo' => $data['numero_processo'],
            'bi' => $data['bi'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'classe' => $data['classe'] ?? null,
            'turma' => $data['turma'] ?? null,
            'ano_lectivo' => $data['ano_lectivo'] ?? null,
            'nacionalidade' => $data['nacionalidade'] ?? 'Angolana',
            'naturalidade' => $data['naturalidade'] ?? null,
            'morada' => $data['morada'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
        ]);

        $sync = [];
        foreach ($data['encarregados'] ?? [] as $enc) {
            $sync[$enc['id']] = [
                'parentesco' => $enc['parentesco'] ?? 'outro',
                'principal' => ! empty($enc['principal']),
            ];
        }
        $aluno->encarregados()->sync($sync);

        return redirect()->route('alunos.index')->with('status', __('Resource updated successfully.'));
    }

    public function destroy(Aluno $aluno)
    {
        $user = $aluno->user;
        $aluno->delete();
        $user?->delete();
        return redirect()->route('alunos.index')->with('status', __('Resource deleted successfully.'));
    }

    protected function validateData(Request $request, ?Aluno $aluno = null): array
    {
        $userId = $aluno?->user_id;
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'numero_processo' => ['required', 'string', 'max:30', Rule::unique('alunos', 'numero_processo')->ignore($aluno?->id)],
            'bi' => ['nullable', 'string', 'max:30'],
            'data_nascimento' => ['nullable', 'date'],
            'sexo' => ['nullable', 'in:M,F'],
            'classe' => ['nullable', 'string', 'max:30'],
            'turma' => ['nullable', 'string', 'max:30'],
            'ano_lectivo' => ['nullable', 'string', 'max:9'],
            'nacionalidade' => ['nullable', 'string', 'max:50'],
            'naturalidade' => ['nullable', 'string', 'max:100'],
            'morada' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
            'encarregados' => ['array'],
            'encarregados.*.id' => ['required', Rule::exists('encarregados', 'id')],
            'encarregados.*.parentesco' => ['nullable', 'in:pai,mae,tutor,irmao,outro'],
            'encarregados.*.principal' => ['nullable', 'boolean'],
        ]);
    }
}
