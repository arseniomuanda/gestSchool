<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue Pessoal Administrativo de Pessoal Auxiliar na tabela
 * `funcionarios` (issue #35).
 *
 * - `categoria` segrega os dois grupos. Default 'administrativo' para
 *   backfill seguro dos registos existentes.
 * - `funcao` é específica de auxiliares (limpeza, segurança, …) — nullable
 *   porque os administrativos não precisam dela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funcionarios', function (Blueprint $t) {
            $t->enum('categoria', ['administrativo', 'auxiliar'])
                ->default('administrativo')
                ->after('numero_funcionario');
            $t->enum('funcao', ['limpeza', 'seguranca', 'motorista', 'jardinagem', 'cantina', 'outro'])
                ->nullable()
                ->after('categoria');
            $t->index('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('funcionarios', function (Blueprint $t) {
            $t->dropIndex(['categoria']);
            $t->dropColumn(['categoria', 'funcao']);
        });
    }
};
