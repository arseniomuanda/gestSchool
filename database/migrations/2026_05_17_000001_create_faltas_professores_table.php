<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faltas dos professores ao serviço (issue #34).
 *
 * Distingue-se das presenças dos alunos (`presencas`): aqui regista-se
 * quando um professor não comparece ao serviço. Pode cobrir uma faixa
 * de tempos do dia (tempo_inicio..tempo_fim).
 *
 * Tipo (justificada/injustificada/licenca) é a MOTIVAÇÃO da ausência.
 * substituto_id é ORTOGONAL — qualquer tipo pode ter substituto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faltas_professores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('professor_id')->constrained('professores')->cascadeOnDelete();
            $t->date('data');
            $t->unsignedSmallInteger('tempo_inicio');
            $t->unsignedSmallInteger('tempo_fim');
            $t->enum('tipo', ['justificada', 'injustificada', 'licenca']);
            $t->text('motivo')->nullable();           // encrypted no model
            $t->foreignId('substituto_id')->nullable()->constrained('professores')->nullOnDelete();
            $t->foreignId('registado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('justificacao_em')->nullable();
            $t->text('documento_url')->nullable();    // placeholder p/ fase 2 (upload)
            $t->timestamps();
            $t->softDeletes();

            $t->index(['professor_id', 'data']);
            $t->index('data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faltas_professores');
    }
};
