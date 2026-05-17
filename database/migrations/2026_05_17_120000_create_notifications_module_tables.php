<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo de Notificações (email + SMS) para encarregados.
 *
 * Credenciais SMTP e Ombala vivem no `.env` (não há UI para as alterar).
 *
 * 3 tabelas:
 * - notification_templates: corpo editável por (evento × canal × locale)
 * - notifications: log do que foi enviado, status, eventuais erros
 * - sms_credit_requests: workflow do Director Geral pedir mais SMS
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $t) {
            $t->id();
            $t->string('event_key');             // 'comunicado_publicado', 'boletim_fechado', etc.
            $t->enum('channel', ['email', 'sms']);
            $t->string('locale', 5)->default('pt');
            $t->string('subject')->nullable();   // só relevante para email
            $t->text('body');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['event_key', 'channel', 'locale']);
        });

        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('recipient_address');     // email ou phone — destinatário final, mesmo se user removido
            $t->enum('channel', ['email', 'sms']);
            $t->string('event_key');             // ex: 'comunicado_publicado', 'boletim_fechado'
            $t->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $t->text('subject')->nullable();
            $t->text('body_preview')->nullable();    // primeiros 200 chars; não cifrado intencionalmente
            $t->timestamp('sent_at')->nullable();
            $t->text('error')->nullable();
            $t->json('payload')->nullable();         // metadata (id do comunicado, aluno, etc.)
            $t->timestamps();
            $t->index(['recipient_user_id', 'created_at']);
            $t->index(['status', 'channel']);
        });

        Schema::create('sms_credit_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedInteger('quantity_requested');
            $t->enum('status', ['pendente', 'enviado', 'aprovado', 'rejeitado'])->default('pendente');
            $t->text('notes')->nullable();
            $t->timestamp('sent_to_ombala_at')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credit_requests');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_templates');
    }
};
