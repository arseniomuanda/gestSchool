<?php

namespace App\Policies;

use App\Models\Professor;
use App\Models\User;

/**
 * Política de acesso a recursos do Professor — em particular o horário pessoal.
 */
class ProfessorPolicy
{
    /**
     * Pode visualizar o horário deste professor.
     * Direcção vê todos; professor só se vê a si próprio.
     */
    public function viewHorario(User $user, Professor $professor): bool
    {
        if ($user->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario'])) {
            return true;
        }

        if ($user->hasAnyRole(['professor', 'professor_assistente'])) {
            return $user->professor?->id === $professor->id;
        }

        return false;
    }

    /**
     * Pode gerir (bulk edit) o horário deste professor — só direcção.
     */
    public function manageHorario(User $user, Professor $professor): bool
    {
        return $user->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario']);
    }
}
