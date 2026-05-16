<?php

namespace App\Http\Controllers;

use App\Models\Professor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfessorController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $professores = Professor::with('user')
            ->when($q, fn ($query) => $query->whereHas('user', function ($w) use ($q) {
                $w->where('name', 'ilike', "%$q%")->orWhere('email', 'ilike', "%$q%");
            }))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('professores.index', compact('professores', 'q'));
    }

    public function create()
    {
        return view('professores.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);
        $user->syncRoles([$data['assistente'] ? 'professor_assistente' : 'professor']);

        Professor::create([
            'user_id' => $user->id,
            'numero_professor' => $data['numero_professor'] ?? null,
            'bi' => $data['bi'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'habilitacoes' => $data['habilitacoes'] ?? null,
            'especialidade' => $data['especialidade'] ?? null,
            'disciplinas' => $data['disciplinas'] ?? null,
            'data_admissao' => $data['data_admissao'] ?? null,
            'assistente' => (bool) ($data['assistente'] ?? false),
            'morada' => $data['morada'] ?? null,
        ]);

        return redirect()->route('professores.index')->with('status', __('Resource created successfully.'));
    }

    public function show(Professor $professor)
    {
        $professor->load([
            'user',
            'atribuicoes.turma.classe',
            'atribuicoes.turma.curso',
            'atribuicoes.turma.anoLectivo',
            'atribuicoes.disciplina',
            'atribuicoes.anoLectivo',
            'turmasDirigidas.classe',
            'turmasDirigidas.anoLectivo',
        ]);

        $anoActivo = \App\Models\AnoLectivo::activo();

        // Atribuições filtradas ao ano activo (se houver)
        $atribuicoesActivas = $anoActivo
            ? $professor->atribuicoes->where('ano_lectivo_id', $anoActivo->id)
            : $professor->atribuicoes;

        // Turmas onde dirige (no ano activo, se houver)
        $turmasDirigidasActivas = $anoActivo
            ? $professor->turmasDirigidas->where('ano_lectivo_id', $anoActivo->id)
            : $professor->turmasDirigidas;

        // Carga horária semanal total (soma de carga_horaria_semanal das disciplinas atribuídas)
        $cargaSemanal = $atribuicoesActivas->sum(fn ($a) => (int) ($a->disciplina->carga_horaria_semanal ?? 0));

        return view('professores.show', [
            'professor' => $professor,
            'anoActivo' => $anoActivo,
            'atribuicoesActivas' => $atribuicoesActivas,
            'turmasDirigidasActivas' => $turmasDirigidasActivas,
            'cargaSemanal' => $cargaSemanal,
        ]);
    }

    public function edit(Professor $professor)
    {
        $professor->load('user');
        return view('professores.edit', compact('professor'));
    }

    public function update(Request $request, Professor $professor)
    {
        $data = $this->validateData($request, $professor);

        $professor->user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $professor->user->password = Hash::make($data['password']);
        }
        $professor->user->save();
        $professor->user->syncRoles([$data['assistente'] ? 'professor_assistente' : 'professor']);

        $professor->update([
            'numero_professor' => $data['numero_professor'] ?? null,
            'bi' => $data['bi'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'habilitacoes' => $data['habilitacoes'] ?? null,
            'especialidade' => $data['especialidade'] ?? null,
            'disciplinas' => $data['disciplinas'] ?? null,
            'data_admissao' => $data['data_admissao'] ?? null,
            'assistente' => (bool) ($data['assistente'] ?? false),
            'morada' => $data['morada'] ?? null,
        ]);

        return redirect()->route('professores.index')->with('status', __('Resource updated successfully.'));
    }

    public function destroy(Professor $professor)
    {
        $user = $professor->user;
        $professor->delete();
        $user?->delete();
        return redirect()->route('professores.index')->with('status', __('Resource deleted successfully.'));
    }

    protected function validateData(Request $request, ?Professor $professor = null): array
    {
        $userId = $professor?->user_id;
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$professor ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'numero_professor' => ['nullable', 'string', 'max:30', Rule::unique('professores', 'numero_professor')->ignore($professor?->id)],
            'bi' => ['nullable', 'string', 'max:30'],
            'data_nascimento' => ['nullable', 'date'],
            'sexo' => ['nullable', 'in:M,F'],
            'habilitacoes' => ['nullable', 'string', 'max:255'],
            'especialidade' => ['nullable', 'string', 'max:150'],
            'disciplinas' => ['nullable', 'string'],
            'data_admissao' => ['nullable', 'date'],
            'assistente' => ['nullable', 'boolean'],
            'morada' => ['nullable', 'string'],
        ]);
    }
}
