# Arquitectura Modular — gestSchool

> **One-pager** dos 9 módulos do domínio SGE Angola. Para detalhes técnicos de cada módulo ver os ficheiros `docs/` específicos e as issues do GitHub.
>
> Cada módulo tem **um dono operacional** (papel da escola que comanda o fluxo) e **entidades primárias** próprias. A regra de ouro é: *se um utilizador real não usa este conceito quando fala, não deve estar no domínio*.

## Os 9 módulos

| # | Módulo | Entidades primárias | Dono | Estado |
|---|---|---|---|---|
| 1 | **Alunos** | Aluno, Matrícula | Secretária | ✅ Implementado |
| 2 | **Encarregados de Educação** | Encarregado, pivot AlunoEncarregado | Secretária | ✅ Implementado |
| 3 | **Corpo Docente** | Professor, Atribuição, **FaltaProfessor** | Director Pedagógico | ✅ Implementado ([#34](https://github.com/arseniomuanda/gestSchool/issues/34)) |
| 4 | **Pessoal Administrativo** | Funcionario (`categoria=administrativo`) | Director Geral | ✅ Implementado ([#35](https://github.com/arseniomuanda/gestSchool/issues/35)) |
| 5 | **Pessoal Auxiliar** | Funcionario (`categoria=auxiliar` + `funcao`) | Director Geral | ✅ Implementado ([#35](https://github.com/arseniomuanda/gestSchool/issues/35)) |
| 6 | **Estrutura Académica** | AnoLectivo, Trimestre, Classe, Curso, Turma, Disciplina, Currículo | Director Pedagógico | ✅ Implementado |
| 7 | **Operação Pedagógica** | Aula, Presença, Avaliação, Nota, Pauta, Boletim, Horário | Professor + Direcção | ✅ Implementado |
| 8 | **Calendário e Eventos** | EventoEscolar | Director | ✅ Implementado |
| 9 | **Biblioteca** | Livro, Empréstimo, Reserva, Multa, papel Bibliotecário | Bibliotecário | ⬜ Por implementar ([#33](https://github.com/arseniomuanda/gestSchool/issues/33)) |

**Funcionario** é uma tabela única segregada por `categoria` (administrativo · auxiliar) — Opção A da issue [#35](https://github.com/arseniomuanda/gestSchool/issues/35). Conceitualmente são módulos separados, mas o schema é partilhado por economia.

## Refutações ao modelo "tudo dentro de Alunos"

Tentação comum no UI: colocar Notas, Turma e Horário como sub-itens do menu "Alunos". **Refutado:**

- **Notas** não pertencem a Alunos — são parte de Pauta/Avaliação (lado professor + trimestre)
- **Turma** não pertence a Alunos — é Estrutura Académica; Aluno aparece via Matrícula
- **Calendário** e **Conteúdo Programático** são projecções, não duplicados em cada perfil

Estes itens são **vistas** em vários módulos, não donos do dado.

## Linguagem de domínio (referência)

| ❌ Não usar | ✅ Usar |
|---|---|
| Pessoas / People | (eliminar — usar os 4 grupos abaixo) |
| Pais | Encarregados de Educação |
| Estudantes | Alunos (ou Educandos no contexto Encarregado) |
| Professores (agregado) | Corpo Docente |
| Funcionários (gaveta única) | Pessoal Administrativo + Pessoal Auxiliar |
| Absence | Falta (ao serviço / às aulas) |

Memory persistente: `~/.claude/projects/-Users-macbook-DEV-gestSchool/memory/sge_angola_terminology.md`.

## Quem vê o quê (papéis vs módulos)

| Papel | Tem acesso a |
|---|---|
| Director Geral | Tudo + Sistema (utilizadores) |
| Director Pedagógico | Módulos 1, 2, 3, 6, 7, 8 |
| Secretário | Módulos 1, 2, 6 (matrículas), 8 |
| Professor / Assistente | Módulos 3 (próprio perfil + faltas), 6 (leitura), 7 (suas atribuições), 8 |
| Encarregado | Portal próprio: educandos · boletins · presenças · comunicados · calendário |
| Aluno | Portal próprio: próprio percurso (leitura) |
| Bibliotecário (a criar) | Módulo 9 + leitura de Aluno/Professor para empréstimos |

## Onde está documentado cada módulo

- **3** Corpo Docente → escola docente em [`docs/`] e via issue [#34](https://github.com/arseniomuanda/gestSchool/issues/34)
- **4 + 5** Pessoal Administrativo + Auxiliar → schema da migration `add_categoria_e_funcao_to_funcionarios` e issue [#35](https://github.com/arseniomuanda/gestSchool/issues/35)
- **6 + 7** Estrutura Académica + Operação Pedagógica → tratamento detalhado no [Roadmap dos Horários](./ROADMAP_HORARIOS.md) e [Conformidade Decreto 162/23](./CONFORMIDADE_DECRETO_162-23.md)
- **9** Biblioteca → especificação ainda na issue [#33](https://github.com/arseniomuanda/gestSchool/issues/33)
- **Cross-cutting**: [Conformidade LPD](./CONFORMIDADE_DECRETO_158-25.md) + [Política de Privacidade](https://gestschool.test/privacidade)

## Próximos passos arquitecturais

1. **Fechar [#33](https://github.com/arseniomuanda/gestSchool/issues/33) — Biblioteca** (último módulo em falta)
2. **Faltas dos Professores Fase 2** ([#34](https://github.com/arseniomuanda/gestSchool/issues/34)): cancelamento automático de aula, upload de justificação, PDF de assiduidade
3. **Pessoal Auxiliar Fase 2** ([#35](https://github.com/arseniomuanda/gestSchool/issues/35)): escalas de turnos, folha de ponto
4. **Comunicação** (módulo 10 implícito) — emails/SMS para encarregados é tecnicamente um módulo próprio. Para já está dentro de "Comunicação" no sidebar com `Comunicado`.
