<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Comunicado extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo', 'conteudo', 'autor_id', 'alcance',
        'classe_id', 'turma_id', 'publicado_em',
    ];

    protected function casts(): array
    {
        return ['publicado_em' => 'datetime'];
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function scopePublicados(Builder $query): Builder
    {
        return $query->whereNotNull('publicado_em')->where('publicado_em', '<=', now());
    }

    /**
     * Destinatários (Users) do comunicado para notificações por email/SMS.
     * Filtra por alcance: todos / professores / encarregados / classe / turma.
     */
    public function destinatariosUsers(): Collection
    {
        $userIds = collect();

        switch ($this->alcance) {
            case 'todos':
                $userIds = User::query()->pluck('id');
                break;

            case 'professores':
                $userIds = User::role(['professor', 'professor_assistente'])->pluck('id');
                break;

            case 'encarregados':
                $userIds = User::role('encarregado')->pluck('id');
                break;

            case 'classe':
                if ($this->classe_id) {
                    $alunoIds = \App\Models\Matricula::query()
                        ->whereHas('turma', fn ($q) => $q->where('classe_id', $this->classe_id))
                        ->where('estado', 'activa')
                        ->pluck('aluno_id');
                    $userIds = \App\Models\Encarregado::query()
                        ->whereHas('alunos', fn ($q) => $q->whereIn('alunos.id', $alunoIds))
                        ->pluck('user_id')
                        ->filter();
                }
                break;

            case 'turma':
                if ($this->turma_id) {
                    $alunoIds = \App\Models\Matricula::query()
                        ->where('turma_id', $this->turma_id)
                        ->where('estado', 'activa')
                        ->pluck('aluno_id');
                    $userIds = \App\Models\Encarregado::query()
                        ->whereHas('alunos', fn ($q) => $q->whereIn('alunos.id', $alunoIds))
                        ->pluck('user_id')
                        ->filter();
                }
                break;
        }

        return User::query()->whereIn('id', $userIds->unique())->get();
    }

    public function scopeVisivelPara(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('alcance', 'todos');
            if ($user->hasAnyRole(['professor', 'professor_assistente'])) {
                $q->orWhere('alcance', 'professores');
            }
            if ($user->hasRole('encarregado')) {
                $q->orWhere('alcance', 'encarregados');
                $alunoIds = $user->encarregado?->alunos()->pluck('alunos.id') ?? collect();
                if ($alunoIds->isNotEmpty()) {
                    $turmaIds = \App\Models\Matricula::whereIn('aluno_id', $alunoIds)->pluck('turma_id')->unique();
                    if ($turmaIds->isNotEmpty()) {
                        $q->orWhere(function ($c) use ($turmaIds) {
                            $c->where('alcance', 'turma')->whereIn('turma_id', $turmaIds);
                        });
                    }
                }
            }
        });
    }
}
