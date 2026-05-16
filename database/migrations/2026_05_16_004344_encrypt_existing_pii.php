<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Cifra os dados pessoais já existentes nas tabelas alvo. Acedemos via
 * DB::table() (sem Eloquent) para contornar o cast 'encrypted' que já
 * está activo nos models — esse cast tentaria desencriptar texto puro
 * e dispararia DecryptException.
 *
 * Heurística de idempotência: payloads cifrados pelo Laravel começam
 * pelo prefixo 'eyJ' (base64 de '{"iv": ...'). Saltamos esses para
 * permitir re-correr a migration com segurança.
 *
 * Pré-requisito: migração 004343 que alarga colunas para `text`.
 */
return new class extends Migration
{
    protected array $alvos = [
        'alunos'        => ['bi', 'naturalidade', 'morada', 'observacoes'],
        'encarregados'  => ['bi', 'profissao', 'local_trabalho', 'morada'],
        'professores'   => ['bi', 'habilitacoes', 'especialidade', 'morada'],
        'funcionarios'  => ['bi', 'cargo', 'departamento', 'morada'],
    ];

    public function up(): void
    {
        foreach ($this->alvos as $tabela => $colunas) {
            DB::table($tabela)->orderBy('id')->chunkById(500, function ($rows) use ($tabela, $colunas) {
                foreach ($rows as $r) {
                    $update = [];
                    foreach ($colunas as $col) {
                        $val = $r->$col ?? null;
                        if ($val === null || $val === '') continue;
                        if (is_string($val) && str_starts_with($val, 'eyJ')) continue;
                        $update[$col] = Crypt::encryptString((string) $val);
                    }
                    if ($update) {
                        DB::table($tabela)->where('id', $r->id)->update($update);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->alvos as $tabela => $colunas) {
            DB::table($tabela)->orderBy('id')->chunkById(500, function ($rows) use ($tabela, $colunas) {
                foreach ($rows as $r) {
                    $update = [];
                    foreach ($colunas as $col) {
                        $val = $r->$col ?? null;
                        if ($val === null || $val === '') continue;
                        if (! is_string($val) || ! str_starts_with($val, 'eyJ')) continue;
                        try {
                            $update[$col] = Crypt::decryptString($val);
                        } catch (\Throwable $e) {
                            // Já não cifrado ou key diferente — saltar
                        }
                    }
                    if ($update) {
                        DB::table($tabela)->where('id', $r->id)->update($update);
                    }
                }
            });
        }
    }
};
