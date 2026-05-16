<?php

/**
 * Parâmetros legais da instituição. Editáveis por release ou via .env.
 *
 * `lpd_versao` deve ser bumped (v1 → v2) sempre que o texto da política
 * de privacidade muda materialmente — os consentimentos antigos passam
 * a estar desfasados e o sistema irá pedir re-consentimento.
 */
return [

    // Versão actual da Política de Privacidade. Bump para forçar
    // re-consentimento dos encarregados.
    'lpd_versao' => env('LPD_VERSAO', 'v1'),

    // Dados do Encarregado da Protecção de Dados (DPO) — designado
    // pela Direcção da Instituição. Substituir no .env de produção.
    'dpo' => [
        'nome' => env('DPO_NOME', 'A definir'),
        'email' => env('DPO_EMAIL', 'dpo@example.test'),
        'telefone' => env('DPO_TELEFONE', null),
    ],

    // Identificação da instituição (Responsável pelo Tratamento)
    'instituicao' => [
        'nome' => env('INSTITUICAO_NOME', 'Instituição de Ensino — GestSchool'),
        'nif' => env('INSTITUICAO_NIF', null),
        'morada' => env('INSTITUICAO_MORADA', 'Luanda, Angola'),
        'email' => env('INSTITUICAO_EMAIL', 'geral@example.test'),
    ],

    // Agência de Protecção de Dados (autoridade fiscalizadora — Lei 22/11)
    'apd' => [
        'nome' => 'Agência de Protecção de Dados (APD)',
        'site' => 'https://apd.ao',
    ],

    // Anos de retenção de dados após a saída/conclusão do aluno.
    // Depois deste prazo, os dados devem ser anonimizados.
    'retencao_anos' => env('RETENCAO_ANOS', 5),

];
