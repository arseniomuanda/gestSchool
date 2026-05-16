<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alarga as colunas PII para `text` antes da cifragem. O payload cifrado
 * do Laravel inflaciona o tamanho ~4-5× (IV+ciphertext+MAC+JSON base64),
 * pelo que um BI de 15 chars produz ~200 chars cifrados — sai do
 * varchar(100) original.
 *
 * Em Postgres, `text` tem o mesmo desempenho que varchar — escolha
 * defensiva e simples.
 */
return new class extends Migration
{
    protected array $alvos = [
        'alunos'       => ['bi', 'naturalidade'],
        'encarregados' => ['bi', 'profissao', 'local_trabalho'],
        'professores'  => ['bi', 'habilitacoes', 'especialidade'],
        'funcionarios' => ['bi', 'cargo', 'departamento'],
    ];

    public function up(): void
    {
        foreach ($this->alvos as $tabela => $colunas) {
            Schema::table($tabela, function (Blueprint $table) use ($colunas) {
                foreach ($colunas as $col) {
                    $table->text($col)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Não revertemos — assumiria que dados são pequenos, mas estão cifrados.
        // Em rollback real corre-se primeiro a 004344 (down) para decifrar.
    }
};
