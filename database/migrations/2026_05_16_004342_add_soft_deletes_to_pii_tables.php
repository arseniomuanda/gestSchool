<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activa SoftDeletes nas tabelas que contêm dados pessoais. Necessário
 * para cumprir o direito à rectificação/eliminação da Lei 22/11 sem
 * destruir registos definitivamente (que poderiam ser auditados).
 */
return new class extends Migration
{
    protected array $tabelas = ['alunos', 'encarregados', 'professores', 'funcionarios'];

    public function up(): void
    {
        foreach ($this->tabelas as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tabelas as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
