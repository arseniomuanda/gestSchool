# Editor de horário da turma

> Constrói o horário semanal completo de uma turma numa só página. Suporta atalhos para acelerar (copy-paste, drag & drop, auto-gerador) e mostra um diagnóstico em tempo real.

## O que vês aqui

- **Grelha semanal**: dias da semana (segunda a sexta) × tempos lectivos (1.º ao 8.º)
- **Toolbar** no topo com clipboard, modo de edição e acções globais
- **Painel Diagnóstico** em baixo com 7 cards (furos, não escaladas, carga horária, concentração, consecutivos, horas difíceis)
- **Legenda** com todas as atribuições disponíveis para esta turma

## O que podes fazer

### Modo Formulário (default)
Selects normais em cada célula — escolhes a atribuição (disciplina + professor) para cada slot e a sala opcional.

### Modo Visual (drag & drop)
Activa no toggle do topo. Cada slot ocupado é um **cartão arrastável**; a lista lateral mostra as atribuições disponíveis.
- **Arrastar do pool para slot vazio** → atribui
- **Arrastar do pool para slot ocupado** → substitui (com aviso)
- **Arrastar slot para slot** → swap ou move
- **Arrastar slot para o pool** → liberta o slot

### Copy/paste de colunas (dias) e linhas (tempos)
Cada cabeçalho tem um menu `⋮` com:
- **Copiar coluna/linha** — guarda no clipboard
- **Colar coluna/linha** — aplica num destino
- **Aplicar a todos os dias** — replica padrão semanal
- **Limpar coluna/linha**

O clipboard sobrevive a refresh (24h em localStorage).

### Sugerir horário automaticamente
Dropdown **Sugerir horário** com 2 opções:
- **Rápido (heurística)** — algoritmo greedy local, ~50ms, sempre disponível
- **Com IA (Gemini)** — chama o modelo Google, 1-3s, requer chave configurada

A proposta popula o estado **sem gravar** — revês visualmente, ajustas, e clicas Guardar.

### Painel Diagnóstico
Atualiza ao vivo enquanto editas:
- 🔴 **Furos** — slots vazios entre dois ocupados no mesmo dia
- 🟡 **Não escaladas** — atribuições com zero tempos
- 🟡 **Tempos em falta** vs carga horária semanal da disciplina
- 🟡 **Concentração diária** — disciplina pesada (MAT/POR/...) com >50% num só dia
- 🟡 **Tempos consecutivos** — professor com >3 tempos seguidos

## Dicas

- O submit final passa sempre pelo servidor que **revalida conflitos** entre turmas (mesmo professor noutra turma à mesma hora).
- O modo formulário e o visual partilham o mesmo estado — alterações num lado aparecem no outro.

## Notas de regulamentação

- Tempo lectivo = **45 minutos** (Decreto Presidencial 162/23, Art. 31.º).
- O decreto exige **5 min de pausa entre tempos** e **15 min entre 3.º e 4.º tempo** no secundário — ver [docs/CONFORMIDADE_DECRETO_162-23.md](https://github.com/arseniomuanda/gestSchool/blob/main/docs/CONFORMIDADE_DECRETO_162-23.md) para a auditoria completa.

## Páginas relacionadas

- [Atribuições](/atribuicoes) — definir professor × disciplina × turma antes de fazer o horário
- [Horários](/horarios) — visão geral por turma e por professor
