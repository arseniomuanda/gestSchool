<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Controla os 2 grupos de funcionários (Pessoal Administrativo e Pessoal Auxiliar).
 * A categoria é detectada pelo nome da rota: rotas começadas por
 * "pessoal-auxiliar." operam sobre auxiliares; o resto é administrativo.
 */
class FuncionarioController extends Controller
{
    public function index(Request $request)
    {
        $categoria = $this->categoriaActual();
        $q = $request->query('q');

        $funcionarios = Funcionario::with('user.roles')
            ->where('categoria', $categoria)
            ->when($q, fn ($query) => $query->whereHas('user', function ($w) use ($q) {
                $w->where('name', 'ilike', "%$q%")->orWhere('email', 'ilike', "%$q%");
            }))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('funcionarios.index', [
            'funcionarios' => $funcionarios,
            'q' => $q,
            'categoria' => $categoria,
            'routeBase' => $this->routeBase(),
        ]);
    }

    public function create()
    {
        return view('funcionarios.create', [
            'roles' => $this->rolesDisponiveis(),
            'categoria' => $this->categoriaActual(),
            'routeBase' => $this->routeBase(),
            'funcoesAuxiliar' => Funcionario::FUNCOES_AUXILIAR,
        ]);
    }

    public function store(Request $request)
    {
        $categoria = $this->categoriaActual();
        $data = $this->validateData($request, null, $categoria);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);
        $user->syncRoles([$data['role']]);

        Funcionario::create([
            'user_id' => $user->id,
            'numero_funcionario' => $data['numero_funcionario'] ?? null,
            'categoria' => $categoria,
            'funcao' => $categoria === 'auxiliar' ? ($data['funcao'] ?? null) : null,
            'bi' => $data['bi'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'cargo' => $data['cargo'] ?? null,
            'departamento' => $data['departamento'] ?? null,
            'data_admissao' => $data['data_admissao'] ?? null,
            'morada' => $data['morada'] ?? null,
        ]);

        return redirect()->route($this->routeBase() . '.index')
            ->with('status', __('Resource created successfully.'));
    }

    public function show(Funcionario $funcionario)
    {
        $this->assertCategoriaMatchesRoute($funcionario);
        $funcionario->load('user.roles');
        return view('funcionarios.show', [
            'funcionario' => $funcionario,
            'routeBase' => $this->routeBase(),
        ]);
    }

    public function edit(Funcionario $funcionario)
    {
        $this->assertCategoriaMatchesRoute($funcionario);
        $funcionario->load('user.roles');
        return view('funcionarios.edit', [
            'funcionario' => $funcionario,
            'roles' => $this->rolesDisponiveis(),
            'categoria' => $funcionario->categoria,
            'routeBase' => $this->routeBase(),
            'funcoesAuxiliar' => Funcionario::FUNCOES_AUXILIAR,
        ]);
    }

    public function update(Request $request, Funcionario $funcionario)
    {
        $this->assertCategoriaMatchesRoute($funcionario);
        $data = $this->validateData($request, $funcionario, $funcionario->categoria);

        $funcionario->user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $funcionario->user->password = Hash::make($data['password']);
        }
        $funcionario->user->save();
        $funcionario->user->syncRoles([$data['role']]);

        $funcionario->update([
            'numero_funcionario' => $data['numero_funcionario'] ?? null,
            'funcao' => $funcionario->categoria === 'auxiliar' ? ($data['funcao'] ?? null) : null,
            'bi' => $data['bi'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'cargo' => $data['cargo'] ?? null,
            'departamento' => $data['departamento'] ?? null,
            'data_admissao' => $data['data_admissao'] ?? null,
            'morada' => $data['morada'] ?? null,
        ]);

        return redirect()->route($this->routeBase() . '.index')
            ->with('status', __('Resource updated successfully.'));
    }

    public function destroy(Funcionario $funcionario)
    {
        $this->assertCategoriaMatchesRoute($funcionario);
        $user = $funcionario->user;
        $funcionario->delete();
        $user?->delete();
        return redirect()->route($this->routeBase() . '.index')
            ->with('status', __('Resource deleted successfully.'));
    }

    // ------------- helpers -------------

    /** Detecta a categoria pelo nome da rota actual. */
    protected function categoriaActual(): string
    {
        $name = Route::currentRouteName() ?? '';
        return str_starts_with($name, 'pessoal-auxiliar.') ? 'auxiliar' : 'administrativo';
    }

    protected function routeBase(): string
    {
        return $this->categoriaActual() === 'auxiliar' ? 'pessoal-auxiliar' : 'funcionarios';
    }

    /** Garante que a rota e o registo correspondem (não permite editar admin via /pessoal-auxiliar). */
    protected function assertCategoriaMatchesRoute(Funcionario $f): void
    {
        if ($f->categoria !== $this->categoriaActual()) {
            abort(404);
        }
    }

    protected function rolesDisponiveis()
    {
        // Auxiliar fica com role 'funcionario' apenas; admin pode ter os 4
        return Role::whereIn('name', $this->categoriaActual() === 'auxiliar'
            ? ['funcionario']
            : ['director_geral', 'director_pedagogico', 'secretario', 'funcionario']
        )->get();
    }

    protected function validateData(Request $request, ?Funcionario $funcionario, string $categoria): array
    {
        $userId = $funcionario?->user_id;
        $allowedRoles = $categoria === 'auxiliar'
            ? ['funcionario']
            : ['director_geral', 'director_pedagogico', 'secretario', 'funcionario'];

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$funcionario ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($allowedRoles)],
            'numero_funcionario' => ['nullable', 'string', 'max:30', Rule::unique('funcionarios', 'numero_funcionario')->ignore($funcionario?->id)],
            'funcao' => [
                $categoria === 'auxiliar' ? 'required' : 'nullable',
                Rule::in(Funcionario::FUNCOES_AUXILIAR),
            ],
            'bi' => ['nullable', 'string', 'max:30'],
            'data_nascimento' => ['nullable', 'date'],
            'sexo' => ['nullable', 'in:M,F'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'data_admissao' => ['nullable', 'date'],
            'morada' => ['nullable', 'string'],
        ]);
    }
}
