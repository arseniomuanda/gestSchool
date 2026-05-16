<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos de consentimento da Lei n.º 22/11 (Protecção de Dados
 * Pessoais) à tabela `matriculas`. O encarregado consente no acto de
 * matrícula; a versão da política é guardada para detectar quando a
 * política muda e o consentimento precisa de ser refeito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->timestamp('consentimento_lpd_em')->nullable()->after('observacoes');
            $table->string('consentimento_lpd_versao', 20)->nullable()->after('consentimento_lpd_em');
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropColumn(['consentimento_lpd_em', 'consentimento_lpd_versao']);
        });
    }
};
