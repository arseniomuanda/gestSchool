<?php

namespace App\Policies;

use App\Models\FaltaProfessor;
use App\Models\User;

class FaltaProfessorPolicy
{
    /** Listar faltas. */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'director_geral', 'director_pedagogico', 'secretario',
            'professor', 'professor_assistente',
        ]);
        // Professor vê a listagem mas o controller filtra para mostrar só as suas.
    }

    /** Ver detalhe de uma falta. */
    public function view(User $user, FaltaProfessor $falta): bool
    {
        if ($user->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario'])) {
            return true;
        }
        if ($user->hasAnyRole(['professor', 'professor_assistente'])) {
            return $user->professor?->id === $falta->professor_id
                || $user->professor?->id === $falta->substituto_id;
        }
        return false;
    }

    /** Criar nova falta — só direcção. */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario']);
    }

    /** Editar — só direcção. */
    public function update(User $user, FaltaProfessor $falta): bool
    {
        return $user->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario']);
    }

    /** Eliminar — só director_geral / pedagógico (não secretário). */
    public function delete(User $user, FaltaProfessor $falta): bool
    {
        return $user->hasAnyRole(['director_geral', 'director_pedagogico']);
    }

    /** Marcar como justificada — direcção. */
    public function justify(User $user, FaltaProfessor $falta): bool
    {
        return $user->hasAnyRole(['director_geral', 'director_pedagogico', 'secretario']);
    }
}
