<?php

namespace App\Http\Controllers;

use App\Models\Encarregado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EncarregadoController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $encarregados = Encarregado::with('user')
            ->when($q, fn ($query) => $query->whereHas('user', function ($w) use ($q) {
                $w->where('name', 'ilike', "%$q%")->orWhere('email', 'ilike', "%$q%");
            }))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('encarregados.index', compact('encarregados', 'q'));
    }

    public function create()
    {
        return view('encarregados.create');
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        $query = Encarregado::query()
            ->join('users', 'users.id', '=', 'encarregados.user_id')
            ->select('encarregados.id', 'encarregados.bi', 'encarregados.profissao', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->limit(20);

        if (mb_strlen($term) >= 2) {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like) {
                $q->where('users.name', 'like', $like)
                    ->orWhere('users.email', 'like', $like)
                    ->orWhere('encarregados.bi', 'like', $like)
                    ->orWhere('encarregados.profissao', 'like', $like);
            });
        }

        return response()->json([
            'results' => $query->get()->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'email' => $r->email,
                'bi' => $r->bi,
                'profissao' => $r->profissao,
            ])->all(),
        ]);
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
        $user->syncRoles(['encarregado']);

        Encarregado::create([
            'user_id' => $user->id,
            'bi' => $data['bi'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'profissao' => $data['profissao'] ?? null,
            'local_trabalho' => $data['local_trabalho'] ?? null,
            'morada' => $data['morada'] ?? null,
        ]);

        return redirect()->route('encarregados.index')->with('status', __('Resource created successfully.'));
    }

    public function show(Encarregado $encarregado)
    {
        $encarregado->load(['user', 'alunos.user']);
        return view('encarregados.show', compact('encarregado'));
    }

    public function edit(Encarregado $encarregado)
    {
        $encarregado->load('user');
        return view('encarregados.edit', compact('encarregado'));
    }

    public function update(Request $request, Encarregado $encarregado)
    {
        $data = $this->validateData($request, $encarregado);

        $encarregado->user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $encarregado->user->password = Hash::make($data['password']);
        }
        $encarregado->user->save();

        $encarregado->update([
            'bi' => $data['bi'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'profissao' => $data['profissao'] ?? null,
            'local_trabalho' => $data['local_trabalho'] ?? null,
            'morada' => $data['morada'] ?? null,
        ]);

        return redirect()->route('encarregados.index')->with('status', __('Resource updated successfully.'));
    }

    public function destroy(Encarregado $encarregado)
    {
        $user = $encarregado->user;
        $encarregado->delete();
        $user?->delete();
        return redirect()->route('encarregados.index')->with('status', __('Resource deleted successfully.'));
    }

    protected function validateData(Request $request, ?Encarregado $encarregado = null): array
    {
        $userId = $encarregado?->user_id;
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$encarregado ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'bi' => ['nullable', 'string', 'max:30'],
            'data_nascimento' => ['nullable', 'date'],
            'sexo' => ['nullable', 'in:M,F'],
            'profissao' => ['nullable', 'string', 'max:100'],
            'local_trabalho' => ['nullable', 'string', 'max:150'],
            'morada' => ['nullable', 'string'],
        ]);
    }
}
