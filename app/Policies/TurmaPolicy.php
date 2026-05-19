<?php

namespace App\Policies;

use App\Models\Turma;
use App\Models\User;

/**
 * Política de acesso por turma. Foco actual: visibilidade de horário.
 *
 * Regras:
 * - Direcção/Secretaria → vê e gere qualquer turma
 * - Professor/Assistente → vê só turmas onde lecciona OU é director de turma
 * - Encarregado → vê turmas onde tem aluno matriculado (apenas leitura)
 */
class TurmaPolicy
{
    /**
     * Pode visualizar o horário desta turma.
     */
    public function viewHorario(User $user, Turma $turma): bool
    {
        if ($user->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario'])) {
            return true;
        }

        if ($user->hasAnyRole(['professor', 'professor_assistente'])) {
            $prof = $user->professor;
            if (! $prof) return false;

            // Caso 1: é director de turma
            if ($turma->director_turma_id === $prof->id) {
                return true;
            }

            // Caso 2: tem atribuição nesta turma (lecciona pelo menos uma disciplina)
            return $turma->atribuicoes()->where('professor_id', $prof->id)->exists();
        }

        // Encarregado: vê horário da turma onde tem aluno matriculado (qualquer estado).
        if ($user->hasRole('encarregado') && $user->encarregado) {
            return \App\Models\Matricula::query()
                ->where('turma_id', $turma->id)
                ->whereIn('aluno_id', $user->encarregado->alunos()->pluck('alunos.id'))
                ->exists();
        }

        return false;
    }

    /**
     * Pode gerir (CRUD bulk) o horário da turma. Restrito à direcção.
     */
    public function manageHorario(User $user, Turma $turma): bool
    {
        return $user->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario']);
    }
}
