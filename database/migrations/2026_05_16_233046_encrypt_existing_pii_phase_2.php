<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Cifra os 5 campos PII adicionados na fase 2 (Lei 22/11).
 *
 * Mesmo padrão da migração 2026_05_16_004344_encrypt_existing_pii:
 * acedemos via DB::table() para contornar o cast 'encrypted' já
 * activo nos models; heurística de idempotência via prefixo 'eyJ'.
 *
 * Pré-requisito: 2026_05_16_233045_widen_pii_columns_phase_2.
 */
return new class extends Migration
{
    protected array $alvos = [
        'users'        => ['phone'],
        'professores'  => ['disciplinas'],
        'matriculas'   => ['observacoes'],
        'presencas'    => ['observacao'],
        'notas'        => ['observacao'],
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
                            // já decifrado ou key diferente — saltar
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
