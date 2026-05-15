# Conformidade com o Decreto Presidencial n.º 162/23

> **Fonte:** *Diário da República, I Série, n.º 142, 1 de Agosto de 2023* — Decreto Presidencial n.º 162/23 que aprova o **Regime Jurídico do Ensino Primário e Secundário do Subsistema de Ensino Geral**.
>
> **Status:** auditoria feita em 2026-05-15. Lacunas identificadas mas não implementadas ainda (decisão de prioridade).

## 1. Regras do decreto relevantes para o software

### 1.1 Tempos lectivos e intervalos — Artigo 31.º

| Item | Regra |
|---|---|
| Duração do tempo | **45 minutos** (1.ª–6.ª Classe + I e II Ciclos Secundário) |
| Ensino Primário (diário) | mínimo 4h lectivas · intervalo de 15 min após as primeiras 2h |
| Ensino Secundário (manhã/tarde) | pausa **5 min** entre cada aula (troca de professores) · intervalo grande **15 min** entre **3.º e 4.º tempos** |
| Ensino Nocturno | 5h lectivas · 45 min cada · pausa 5 min entre aulas |

### 1.2 Organização do horário — Artigo 32.º

Critérios obrigatórios para a elaboração:
- Carga horária
- **Complexidade da disciplina**
- **Coeficiente de fadiga**
- Número de salas de aulas
- Número de professores

Regras específicas:
- Disciplinas com **tempos lectivos duplos** ≥ 1×/semana: **Língua Portuguesa, Matemática, Educação Visual e Plástica**
- **I Ciclo Secundário:** tempos duplos **não devem ser** em dias nem em tempos seguidos
- **II Ciclo Secundário:** disciplinas dadas em **bloco de duas aulas**, sempre que possível
- **Educação Física:** programada no **período oposto** ao habitual sempre que possível

### 1.3 Carga horária — Artigos 45.º, 49.º, 50.º

| Nível | Semanal (mínimo) | Anual (mínimo) |
|---|---|---|
| Iniciação (na Escola Primária) | 20 tempos | — |
| Ensino Primário (1.ª–6.ª) | 24 tempos | — |
| I Ciclo Secundário (7.ª–9.ª) | 30 tempos | 900 tempos |
| II Ciclo Secundário — formação geral | 18 tempos | — |
| II Ciclo Secundário — Ciências Físicas e Biológicas | 30 tempos | — |
| II Ciclo Secundário — Económico-Jurídicas | 27–30 | — |
| II Ciclo Secundário — Humanas | 25–27 | — |
| II Ciclo Secundário — Artes Visuais | 25–29 | — |
| II Ciclo Secundário (qualquer área) | — | **750 tempos** |

### 1.4 Outras regras relacionadas

- **Turnos:** manhã, tarde, noite (Artigo 18.º)
- **Monodocência:** 1.ª–4.ª Classes em regime de um único professor (Artigo 44.º)
- **Idade de ingresso:** 1.ª Classe = 6 anos completos (Artigo 21.º)

## 2. Auditoria do código actual

### 2.1 Estado actual — `config/escola.php`

```php
'dias_lectivos' => [1, 2, 3, 4, 5],
'tempos_lectivos' => [
    1 => ['07:30', '08:15'],
    2 => ['08:15', '09:00'],
    3 => ['09:15', '10:00'],
    4 => ['10:00', '10:45'],
    5 => ['11:00', '11:45'],
    6 => ['11:45', '12:30'],
    7 => ['13:30', '14:15'],
    8 => ['14:15', '15:00'],
],
```

### 2.2 Gaps face ao decreto

| # | Regra do decreto | Estado actual | Severidade |
|---|---|---|---|
| 1 | Pausa 5 min entre tempos (secundário) | Tempos 1→2, 5→6, 7→8 estão encadeados sem pausa | 🔴 Alta |
| 2 | Intervalo 15 min entre **3.º e 4.º** | Temos entre 2-3 e entre 4-5 (errado) | 🔴 Alta |
| 3 | Intervalo 15 min após 2h (primário) | Não diferencia primário | 🟡 Média |
| 4 | Carga diária por nível | Lista única para todos | 🟡 Média |
| 5 | Turno muda os tempos | `Turma.turno` existe mas não muda os tempos lectivos | 🟡 Média |
| 6 | Tempos duplos por disciplina | Inexistente | 🟡 Média |
| 7 | Coeficiente de fadiga | Inexistente | 🟢 Baixa |
| 8 | Bloco de 2 aulas (II Ciclo) | Inexistente | 🟢 Baixa |
| 9 | Educação Física em período oposto | Não modelado | 🟢 Baixa |
| 10 | Validar carga semanal/anual mínima | Não validado | 🟡 Média |

## 3. Proposta de schema dinâmico (aprovada)

**Decisão (2026-05-15):**
- Config rica em PHP, indexada por **nível + turno**
- Pausas/intervalos/almoço como **entidades separadas** no array (não inferidos)
- Migração adiada — abrir aqui como TODO

### 3.1 Estrutura proposta

```php
// config/escola.php
'horario_perfis' => [
    'primario.manha' => [
        ['tipo' => 'lectivo',   'numero' => 1, 'inicio' => '07:30', 'fim' => '08:15'],
        ['tipo' => 'lectivo',   'numero' => 2, 'inicio' => '08:15', 'fim' => '09:00'],
        ['tipo' => 'intervalo', 'inicio' => '09:00', 'fim' => '09:15'],
        ['tipo' => 'lectivo',   'numero' => 3, 'inicio' => '09:15', 'fim' => '10:00'],
        ['tipo' => 'lectivo',   'numero' => 4, 'inicio' => '10:00', 'fim' => '10:45'],
    ],

    'secundario.manha' => [
        ['tipo' => 'lectivo', 'numero' => 1, 'inicio' => '07:30', 'fim' => '08:15'],
        ['tipo' => 'pausa',   'inicio' => '08:15', 'fim' => '08:20'],
        ['tipo' => 'lectivo', 'numero' => 2, 'inicio' => '08:20', 'fim' => '09:05'],
        ['tipo' => 'pausa',   'inicio' => '09:05', 'fim' => '09:10'],
        ['tipo' => 'lectivo', 'numero' => 3, 'inicio' => '09:10', 'fim' => '09:55'],
        ['tipo' => 'intervalo', 'inicio' => '09:55', 'fim' => '10:10'],   // 15min entre 3º e 4º
        ['tipo' => 'lectivo', 'numero' => 4, 'inicio' => '10:10', 'fim' => '10:55'],
        ['tipo' => 'pausa',   'inicio' => '10:55', 'fim' => '11:00'],
        ['tipo' => 'lectivo', 'numero' => 5, 'inicio' => '11:00', 'fim' => '11:45'],
        ['tipo' => 'pausa',   'inicio' => '11:45', 'fim' => '11:50'],
        ['tipo' => 'lectivo', 'numero' => 6, 'inicio' => '11:50', 'fim' => '12:35'],
    ],

    'secundario.tarde' => [/* análogo, começando ~13:00 */],
    'secundario.noite' => [/* 5 lectivos + pausas 5 min */],
],

// Disciplinas que requerem tempos duplos pelo menos 1×/semana
'disciplinas_tempos_duplos' => ['POR', 'MAT', 'EVP'],

// Carga horária semanal mínima por nível/ciclo (validação)
'carga_horaria_minima' => [
    'iniciacao' => 20,
    'primario' => 24,
    'secundario.i_ciclo' => 30,
    'secundario.ii_ciclo.geral' => 18,
],
```

### 3.2 Mudanças necessárias no código

- [ ] Helper `Horario::perfilDaTurma(Turma): string` que devolve `'primario.manha'` etc. a partir do `nivel` da classe e `turno` da turma
- [ ] Helper `Horario::temposLectivos(string $perfil): array` que filtra só blocos `tipo === 'lectivo'`
- [ ] Substituir `config('escola.tempos_lectivos')` por `Horario::temposLectivos($perfil)` em:
  - `HorarioController::bulkTurma()` e `bulkProfessor()`
  - `HorarioAnalyser` (furos, carga horária)
  - `HorarioGenerator` (greedy)
  - `HorarioSugestor` (AI prompt)
- [ ] Validação client-side e server-side de `tempo` deve aceitar o intervalo do perfil, não 1-8 fixo
- [ ] UI bulk editor: cabeçalho da linha mostra `Nº - HH:MM-HH:MM` + cor diferente para slots de pausa/intervalo (read-only)
- [ ] `HorarioAnalyser::cargaHoraria()` valida soma vs `carga_horaria_minima[nivel]`
- [ ] Plano de testes: cada perfil renderiza correctamente; submit não permite slot em hora não-lectiva
- [ ] Adicionar à PDF do horário a linha do intervalo (já não fica vazio)

### 3.3 Itens fora deste schema (potenciais fases futuras)

- **Coeficiente de fadiga por disciplina** → campo `Disciplina.coeficiente_fadiga` + peso no scoring do greedy
- **Tempos duplos como conceito** → criar tabela `tempos_duplos_propostos` ou flag em `Atribuicao` (`requer_tempo_duplo`)
- **Bloco de 2 aulas (II Ciclo)** → preferência no scoring do greedy quando `turma.classe.nivel === 'medio'`
- **Educação Física em período oposto** → atributo na `Disciplina` ou heurística no generator

## 4. Próximos passos

Quando esta conformidade for retomada:

1. Criar branch `feat/horario-perfis`
2. Implementar a config rica + helpers (item 3.2 acima)
3. Migrar consumidores um a um, com tests
4. Adicionar UI para director ver/comparar com regulamento (opcional)
5. Validações de carga semanal/anual nos commits do `bulkTurmaStore`

Estimativa: **4-6h** de implementação + testes + UI ligeira.
