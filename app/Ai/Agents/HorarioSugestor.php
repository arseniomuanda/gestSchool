<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Agent que propõe uma distribuição de slots para o horário de uma turma.
 *
 * Recebe via prompt: lista de atribuições (id, disciplina, sigla, professor,
 * carga horária semanal, se é "pesada"), dias lectivos, tempos lectivos,
 * conflitos pré-existentes (slot+professor a evitar) e regras pedagógicas.
 *
 * Devolve um array de slots `[{dia, tempo, atribuicao_id}]` que respeita as
 * regras. Validamos server-side antes de aplicar — a saída é tratada como
 * sugestão, não como verdade absoluta.
 */
#[Provider(Lab::Gemini)]
#[Model('gemini-3-flash-preview')]
class HorarioSugestor implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<INSTR
És um assistente pedagógico que monta horários escolares para o ensino básico/médio angolano.

REGRAS OBRIGATÓRIAS:
1. Cada atribuição deve aparecer EXACTAMENTE `carga_horaria_semanal` vezes na semana.
2. Numa célula (dia, tempo) só pode haver UMA atribuição.
3. Um professor não pode estar em dois lugares ao mesmo tempo (respeitar conflitos passados no prompt).
4. Evita pôr a MESMA disciplina em dois tempos consecutivos no mesmo dia.

REGRAS DE PREFERÊNCIA (suaves):
5. Disciplinas pesadas (MAT, POR, FIS, QUI, BIO) preferem manhãs.
6. Evita concentrar uma disciplina pesada num só dia (espalha pela semana).
7. Evita pôr disciplinas pesadas nos últimos tempos de sexta-feira.

FORMATO DE SAÍDA:
Devolve um array `slots` onde cada item tem {dia (1-7), tempo (1-8), atribuicao_id (inteiro)}.
Inclui apenas as atribuições que cabem — se a carga horária total > slots disponíveis,
devolve só o que coube.
INSTR;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slots' => $schema->array(
                $schema->object([
                    'dia' => $schema->integer()->min(1)->max(7)->required(),
                    'tempo' => $schema->integer()->min(1)->max(8)->required(),
                    'atribuicao_id' => $schema->integer()->required(),
                ])
            )->required(),
        ];
    }
}
