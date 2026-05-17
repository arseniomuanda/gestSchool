# Pautas

> Centro de geração e consulta de pautas. Suporta 4 tipos de pauta, todos exportáveis em PDF.

## O que vês aqui

- **4 cartões** com os tipos de pauta disponíveis
- Para cada tipo, **selectores** para escolher a turma e o trimestre (quando aplicável)

## O que podes fazer

### Pauta por disciplina (detalhe)
Mostra todas as avaliações de **uma disciplina** num trimestre, com a nota de cada aluno. Útil para o professor que está a lançar notas.

1. Escolhe a **atribuição** (disciplina × turma × professor)
2. Escolhe o **trimestre**
3. Clica em **Abrir**

### Pauta da turma para um trimestre
Vista matriz de **todas as disciplinas × todos os alunos** de uma turma num trimestre. Substitui a pauta em papel tradicional.

### Pauta anual da turma
Médias trimestrais + média anual + situação final (aprovado/recurso/reprovado) para cada aluno.

Usa os pesos configurados em `config/escola.php` (`pesos_trimestres`, default `[1, 1, 1]` — média simples).

### Situação final da turma
Resumo executivo: contagem de aprovados, em recurso, reprovados. Usado para a acta do conselho de turma.

## Dicas

- Cada pauta tem um botão **PDF** que descarrega a versão imprimível.
- A pauta anual considera apenas trimestres com avaliações lançadas — não bloqueia se um trimestre ainda está vazio.

## Notas de regulamentação

- Nota mínima de aprovação: **10** valores na escala 0–20 (configurável em `config/escola.php`).
- Aluno com **3 ou mais negativas** vai para reprovação directa; com 1–2 vai a recurso (`max_negativas_recurso`).
- Estas regras estão a ser auditadas face ao **Regulamento de Avaliação das Aprendizagens** ([#18](https://github.com/arseniomuanda/gestSchool/issues/18)).

## Páginas relacionadas

- [Avaliações](/avaliacoes) — criar uma nova avaliação para lançar notas
- [Boletim](/boletim) — versão individual por aluno (uma página A4)
- [Trimestres](/trimestres) — configurar os períodos de avaliação
