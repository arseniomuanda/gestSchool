# Instruções para Claude — gestSchool

Ficheiro de regras de comportamento, convenções e atalhos para qualquer agente
Claude (Code, web, mobile) que trabalhe neste projecto.

---

## Regra de ouro — Git é exclusivo do utilizador

**Não criar branches novas. Não fazer `git commit`. Não abrir Pull Requests.**

O utilizador é o único que escreve no git. Implementa, testa, mostra o trabalho
— mas qualquer escrita em git é decisão e acção dele.

**Permitido:**
- Modificar ficheiros no working tree à vontade
- Correr `composer`, `npm`, `php artisan`, smoke tests, builds
- `git status`, `git diff`, `git log` para inspecção
- `git add` para preparar staging
- `git checkout <branch existente>`

**Proibido sem instrução literal:**
- `git commit`
- `git checkout -b`, `git branch <nova>`, `git switch -c`
- `git push -u` de uma branch nova
- `gh pr create`, `gh pr merge`

Quando o trabalho estiver pronto, mostra o resumo de `git status`/`diff` e
**pára**. Só actua sobre git se o utilizador disser literalmente "commit",
"cria branch X", "abre PR" — não por interpretação.

---

## Idioma

- **Comunicação com o utilizador**: Português (PT-AO/PT-PT)
- **Strings de UI**: sempre via `__()` (suporta `pt.json` e `en.json` em `lang/`).
  Nunca hard-coded em Blade/PHP
- **Identificadores no código**: inglês para framework (controllers, jobs,
  services), português para domínio (modelos, colunas, métodos de regra de
  negócio: `matriculaActiva()`, `disciplinasParaCurso()`)
- **Commits e PRs**: preferencialmente PT, inglês aceite

---

## Terminologia do domínio (SGE Angola)

Este é um Sistema de Gestão Escolar para o ensino angolano. **Não usar termos
genéricos como "Pessoas/People" como agregado** — usar os termos correctos:

| Termo | Significado |
|---|---|
| Aluno / Estudante | Pessoa matriculada na escola |
| Encarregado | Encarregado de Educação (pai, mãe, tutor) |
| Professor | Docente |
| Funcionário | Pessoal não-docente (secretaria, direcção) |
| Matrícula | Inscrição de um aluno num ano lectivo + turma |
| Turma | Grupo de alunos da mesma classe + curso num ano lectivo |
| Classe | Ano de escolaridade (1ª à 13ª) |
| Curso | Especialização do Ensino Médio (Informática, GRH, etc.) |
| Atribuição | Professor × Turma × Disciplina × Ano lectivo |
| Pauta | Folha de notas por disciplina/trimestre |
| Boletim | Avaliação individual do aluno |
| Trimestre | Período lectivo (3 por ano) |

A app está organizada em 9 módulos lógicos: Pessoas, Estrutura Académica,
Operação Pedagógica, Avaliação, Comunicação, Horários, Calendário, Pesquisa
Global, Conformidade Legal (LPD).

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.4, Laravel 13, Spatie Permission |
| Frontend | Blade + Alpine.js + Tailwind 4 (Vite) |
| Base de dados | PostgreSQL 18 (default em Docker) ou SQLite |
| AI | Laravel AI SDK (`laravel/ai`), provider default: **Gemini** |
| PDF | barryvdh/laravel-dompdf |
| Docker | docker-compose + Makefile (`make start`) |

---

## Convenções de UI

- **Componentes Blade existentes**: `<x-card>`, `<x-btn>`, `<x-badge>`,
  `<x-input>`, `<x-select>`, `<x-combobox>`, `<x-combobox-multi>`,
  `<x-page-header>`, `<x-stat-card>`, `<x-data-table>`, `<x-empty>`,
  `<x-modal>`, `<x-confirm-dialog>`. Usar sempre que existir — não duplicar.
- **Nunca usar `window.confirm`, `window.alert`, `window.prompt`**: usar modal
  Alpine + design system (`<x-confirm-dialog>`).
- **Tailwind only**: sem CSS custom inline, sem outras frameworks. Classes
  semânticas vivem em `resources/css/app.css` no `@layer components`.
- **Ícones**: [Lucide](https://lucide.dev) via `<x-lucide-icon-name>`.
- **Padrão de tabelas**: `<x-data-table>` cobre listagens com pesquisa, filtros
  e paginação.

Documento de referência completo: [`DESIGN_SYSTEM.md`](DESIGN_SYSTEM.md).

---

## AI features

- **Provider default**: `Lab::Gemini` (não OpenAI, não Anthropic)
- **Modelo default**: `gemini-3-flash-preview` (`gemini-2.0-flash` tem quota=0
  nesta chave — não usar)
- Config: `config/ai.php`, key em `GEMINI_API_KEY`
- Agentes em `app/Ai/Agents/`, seguir padrão do `HorarioSugestor`

---

## Atalhos de desenvolvimento

```bash
# Setup completo (Docker)
make start                    # bootstrap + runner num único comando

# Sem Docker
composer install && npm install
php artisan migrate --seed
composer run dev

# Reseed do zero (40s)
php artisan migrate:fresh --seed

# Linter
./vendor/bin/pint

# Testes
composer test
```

Conta demo principal: **director@gestschool.test** / `password` (acesso total).
Outras contas em [`README.md`](README.md#perfis-de-acesso).

---

## Antes de "concluir" uma tarefa

1. **Build limpo**: `npm run build` para garantir que assets compilam
2. **Smoke test HTTP**: usar `curl` com sessão autenticada para confirmar que
   rotas afectadas devolvem 200, não 500
3. **`git status` final**: mostrar ao utilizador o que mudou, sem commitar
4. **Não inventar features** além do que foi pedido. Se vires algo a melhorar
   no caminho, menciona — não faças

---

## Referências internas

- [`DESIGN_SYSTEM.md`](DESIGN_SYSTEM.md) — paleta, tokens, componentes
- [`README.md`](README.md) — quickstart, perfis demo, comandos
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — como contribuir
- [`docs/DOCKER.md`](docs/DOCKER.md) — setup Docker completo, troubleshooting
- [`docs/ROADMAP_HORARIOS.md`](docs/ROADMAP_HORARIOS.md) — roadmap horários (4 fases)
- [`docs/CONFORMIDADE_DECRETO_162-23.md`](docs/CONFORMIDADE_DECRETO_162-23.md) — auditoria Ensino Primário e Secundário
- [`docs/CONFORMIDADE_DECRETO_158-25.md`](docs/CONFORMIDADE_DECRETO_158-25.md) — auditoria Dupla Certificação
