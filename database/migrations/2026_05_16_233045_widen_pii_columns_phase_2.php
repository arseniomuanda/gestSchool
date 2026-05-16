<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 da encriptação PII (Lei 22/11): alarga as colunas varchar
 * que vão receber cifras Laravel (~5× o tamanho original).
 *
 * Pares (tabela → coluna):
 *   - users.phone         varchar(30)  → text
 *   - presencas.observacao varchar(255) → text
 *   - notas.observacao    varchar(255) → text
 *
 * As outras alvo da fase 2 já são `text` (professores.disciplinas,
 * matriculas.observacoes) e ficam intactas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->text('phone')->nullable()->change();
        });
        Schema::table('presencas', function (Blueprint $t) {
            $t->text('observacao')->nullable()->change();
        });
        Schema::table('notas', function (Blueprint $t) {
            $t->text('observacao')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Não reverter — dados ficam cifrados e text é compatível.
    }
};
