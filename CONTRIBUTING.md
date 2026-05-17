# Contribuir para o gestSchool

Obrigado pelo interesse em contribuir 🙌

Este documento descreve como propor mudanças, reportar problemas e participar no projecto.
A leitura demora ~5 minutos e poupa tempo a toda a gente.

> Antes de começar, lê a [Licença](LICENSE). Ao submeter qualquer contribuição, aceitas
> que ela seja licenciada nos mesmos termos do projecto (cláusula 5 da Licença).

---

## Índice

- [Código de conduta](#c%C3%B3digo-de-conduta)
- [Tipos de contribuição](#tipos-de-contribui%C3%A7%C3%A3o)
- [Antes de começar](#antes-de-come%C3%A7ar)
- [Reportar bugs](#reportar-bugs)
- [Sugerir funcionalidades](#sugerir-funcionalidades)
- [Setup do ambiente](#setup-do-ambiente)
- [Padrões de código](#padr%C3%B5es-de-c%C3%B3digo)
- [Convenção de commits](#conven%C3%A7%C3%A3o-de-commits)
- [Fluxo de Pull Request](#fluxo-de-pull-request)
- [Tradução](#tradu%C3%A7%C3%A3o)
- [Vulnerabilidades de segurança](#vulnerabilidades-de-seguran%C3%A7a)
- [Áreas que precisam de ajuda](#%C3%A1reas-que-precisam-de-ajuda)

---

## Código de conduta

Esperamos respeito mútuo em todas as interacções (issues, PRs, discussões).
São bem-vindas pessoas de qualquer nível de experiência, género, origem, religião
ou afiliação política. Não toleramos:

- Linguagem ofensiva, discriminatória ou de assédio
- Ataques pessoais ou trolling
- Publicação de informação privada de terceiros sem consentimento

Quem não respeitar estas regras será removido das discussões. Reporta
comportamentos inadequados ao [maintainer](https://github.com/arseniomuanda)
por mensagem directa.

---

## Tipos de contribuição

Não precisas saber Laravel para contribuir:

| Tipo | Exemplos |
|---|---|
| **Código** | Correcção de bugs, novas funcionalidades, refactorings |
| **Documentação** | README, guias de utilização, docstrings |
| **Tradução** | Melhorar `pt.json` / `en.json`, adicionar novas línguas |
| **UX/UI** | Sugerir melhorias visuais, fluxos mais simples, mockups |
| **Testes** | Adicionar testes (PHPUnit) — área especialmente carenciada |
| **Reportar bugs** | Issues bem documentadas com passos para reproduzir |
| **Feedback de uso** | Contar como a tua escola usa o sistema, o que falta |

---

## Antes de começar

1. **Procura primeiro** — verifica nas [issues abertas](https://github.com/arseniomuanda/gestSchool/issues)
   se já existe algo sobre o que queres fazer. Se sim, comenta a dizer que vais trabalhar nisso.
2. **Para mudanças grandes** (nova feature, refactoring transversal, mudança de schema), **abre uma
   issue antes** a propor a abordagem. Evita o desperdício de fazer um PR que não vai ser aceite.
3. **Para correcções pequenas** (typos, bugs óbvios, traduções), vai directo ao PR.

---

## Reportar bugs

Abre uma [issue](https://github.com/arseniomuanda/gestSchool/issues/new) com:

- **Título claro** descrevendo o sintoma (ex: "Boletim do aluno mostra média errada quando há nota em falta")
- **Versão** do gestSchool (commit hash) e do PHP (`php -v`)
- **Passos para reproduzir** numerados (1, 2, 3...)
- **Comportamento esperado** vs **comportamento observado**
- **Screenshots** ou logs relevantes (cuidado para não incluir dados pessoais reais)
- **Ambiente**: SO, navegador, base de dados (SQLite/MySQL/Postgres)

Quanto mais fácil for reproduzir, mais rápido o bug é resolvido.

---

## Sugerir funcionalidades

Abre uma issue com a etiqueta `enhancement` (ou pede para ser adicionada). Inclui:

- **Problema** que estás a tentar resolver (não a solução)
- **Quem beneficia** (que perfil de utilizador, que tipo de escola)
- **Como funciona hoje** (workaround actual, se existe)
- **Proposta** ao alto nível, sem entrar em detalhes de implementação

---

## Setup do ambiente

Segue o guia de [Instalação Rápida](README.md#instala%C3%A7%C3%A3o-r%C3%A1pida) no README.

Resumo:

```bash
git clone https://github.com/arseniomuanda/gestSchool.git
cd gestSchool
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
composer run dev
```

Depois de fazer mudanças no schema, corre `php artisan migrate:fresh --seed` para validar
que os seeders continuam a funcionar end-to-end (~40s).

---

## Padrões de código

### PHP

- **PHP 8.2+** — usa features modernas (constructor promotion, enums, readonly properties, match)
- **Pint** (Laravel padrão) é a fonte de verdade da formatação. Antes de commitar:
  ```bash
  ./vendor/bin/pint
  ```
- **PSR-12** + convenções Laravel
- **Type hints sempre** que possível, incluindo `declare(strict_types=1)` em ficheiros novos
  (gradualmente — não precisas adicionar em ficheiros antigos)
- **Nomes de classes em inglês** (segue o que já existe: `Aluno`, `Professor`, `Matricula`...
  porque o domínio é em PT). Métodos podem ser em PT quando descrevem regra de negócio
  (`matriculaActiva()`, `disciplinasParaCurso()`)

### Blade

- Componentes em `resources/views/components/` reutilizáveis
- Strings sempre via `__('...')` — nunca hard-coded
- Tailwind apenas: nada de CSS custom sem boa razão
- Lucide icons via `<x-lucide-...>` (tens [a lista aqui](https://lucide.dev/icons))

### Migrations

- **Nomes descritivos**: `2026_05_15_create_X_table` ou `add_Y_to_X_table`
- **Foreign keys** sempre com `cascadeOnDelete()` ou `nullOnDelete()` — pensa antes de qual usar
- **Índices** em colunas usadas em filtros/joins (`whereX`, `orderBy`)
- **Reverter** sempre com `down()` que desfaz tudo

### Idioma

- **Comentários, commits, PRs**: preferencialmente em **Português (PT-AO/PT-PT)**, mas inglês é aceite
- **Strings de UI**: em `pt.json` e `en.json`. Se adicionares uma chave nova, adiciona em ambos
- **Identificadores no código**: inglês para framework (controllers, jobs), PT para domínio (modelos, colunas)

---

## Convenção de commits

Usamos uma versão simplificada do [Conventional Commits](https://www.conventionalcommits.org/):

```
<tipo>: <descrição curta no imperativo>

[corpo opcional explicando o porquê, não o como]
```

Tipos aceites:

| Tipo | Quando usar |
|---|---|
| `feat` | Nova funcionalidade |
| `fix` | Correcção de bug |
| `refactor` | Mudança de código que não altera comportamento |
| `docs` | Documentação |
| `style` | Formatação, espaços, sem lógica |
| `test` | Adicionar/corrigir testes |
| `chore` | Build, dependências, configuração |
| `i18n` | Traduções |

**Exemplos bons:**

```
feat: adicionar exportação de boletim em PDF
fix: corrigir cálculo da média final quando há trimestre sem notas
docs: explicar como mudar de SQLite para MySQL
i18n: completar tradução de pt.json para a área de comunicados
```

**Exemplos maus:**

```
update                       ❌ vago demais
fix bug                      ❌ que bug?
WIP                          ❌ não commitar trabalho em curso
asdfgh                       ❌ sério?
Coisas várias da semana      ❌ um commit, uma mudança lógica
```

Mensagens curtas (≤72 caracteres) no título. Detalhes vão para o corpo da mensagem ou para
a descrição do PR.

---

## Fluxo de Pull Request

1. **Fork** do repositório para a tua conta
2. **Cria uma branch** com nome descritivo:
   ```bash
   git checkout -b feat/exportar-boletim-pdf
   git checkout -b fix/media-trimestre-sem-notas
   git checkout -b docs/setup-mysql
   ```
3. **Faz as mudanças** seguindo os [padrões de código](#padr%C3%B5es-de-c%C3%B3digo)
4. **Corre Pint** antes de commitar:
   ```bash
   ./vendor/bin/pint
   ```
5. **Testa manualmente** o fluxo afectado:
   - Login com pelo menos 2 perfis (admin + professor, por exemplo)
   - Criar/editar/eliminar a entidade afectada
   - Verificar que a UI não rebenta em mobile
6. **Commit** com mensagem clara (ver [convenção](#conven%C3%A7%C3%A3o-de-commits))
7. **Push** para o teu fork e abre um Pull Request

### Descrição do PR

Inclui:

- **Porquê** — que problema resolve
- **O quê** — resumo das mudanças (1-3 bullets)
- **Como testar** — passos para validar localmente
- **Screenshots/GIFs** se afectar a UI
- **Issues relacionadas** — `Closes #42`, `Refs #15`

Template sugerido:

```markdown
## Porquê
A média do boletim estava incorrecta quando uma nota faltava num trimestre.

## O quê
- Tratar notas em falta como zero apenas no cálculo da média anual final
- Adicionar aviso visual no boletim quando há notas em falta

## Como testar
1. Reseedar: `php artisan migrate:fresh --seed`
2. Login como `professor@gestschool.test`
3. Apagar uma nota de qualquer aluno do trimestre 1
4. Ver o boletim → média anual deve calcular correctamente

Closes #42
```

### Revisão

- O autor (Arsénio Muanda) revê os PRs. Resposta esperada em **3-7 dias**
- Comentários de revisão são sugestões — discussão respeitosa é bem-vinda
- PRs aprovados são merged via **squash** (1 commit por PR)
- PRs sem actividade do autor durante 30 dias são fechados (podes reabrir)

---

## Tradução

O sistema suporta `pt` (padrão) e `en`. Os ficheiros estão em `lang/`.

Para adicionar uma língua nova (ex: francês):

1. Cria `lang/fr.json` copiando de `pt.json`
2. Traduz os valores (não as chaves)
3. Adiciona a opção no `LocaleController` e no menu de idiomas
4. Abre PR

Para corrigir traduções existentes, abre PR com label `i18n`.

Línguas que adoraríamos ver: **Kimbundu**, **Umbundu**, **Kikongo**, **Francês**.

### Conflitos em `lang/*.json` (importante)

Os ficheiros JSON de tradução conflictavam com frequência quando duas branches
adicionavam chaves em paralelo (ambas inseriam "no fim" do array). Temos agora
**duas defesas combinadas**:

**1. `.gitattributes` com `merge=union`** — automático, em todos os clones:
em conflitos, o git **junta ambos os lados** em vez de marcar conflito.

**2. Script `bin/normalize-lang.sh`** — ordena alfabeticamente, remove
duplicados e valida JSON. Corre manualmente sempre que mexeres em `lang/*.json`:

```bash
bin/normalize-lang.sh
git add lang/*.json
```

**Para garantir que nunca te esqueces**, instala-o como hook `pre-commit` local
(simbolic link, não vai para git):

```bash
ln -sf ../../bin/normalize-lang.sh .git/hooks/pre-commit
```

A partir daí, cada `git commit` normaliza automaticamente os ficheiros lang.

> **Pré-requisito:** `jq` (no macOS: `brew install jq`; Ubuntu: `apt install jq`).

---

## Vulnerabilidades de segurança

**Não abras issue pública** para vulnerabilidades de segurança.

Reporta directamente ao maintainer por mensagem privada no GitHub
([@arseniomuanda](https://github.com/arseniomuanda)) com:

- Descrição da vulnerabilidade
- Passos para reproduzir
- Impacto estimado
- Sugestão de mitigação (se tiveres)

Tentamos responder em **48 horas** e corrigir em **2 semanas** dependendo da severidade.
Reconhecimento público (se quiseres) feito após o fix estar deployed.

---

## Áreas que precisam de ajuda

Se queres contribuir mas não sabes por onde começar:

- 🧪 **Testes automatizados** — quase tudo está sem testes. Cada teste novo é ouro.
- 📄 **Exportação para PDF** — pautas, boletins, listas de presenças
- 🌍 **Traduções** — completar `en.json`, adicionar línguas nacionais angolanas
- ♿ **Acessibilidade** — auditoria WCAG, navegação por teclado, contraste, ARIA
- 📱 **Responsividade** — várias views ainda não estão optimizadas para mobile
- 📊 **Dashboards** — gráficos de aproveitamento, faltas, ocupação de turmas
- 📚 **Documentação para administradores** — guias passo-a-passo para directores escolares
- 🐛 **Triagem de issues** — reproduzir bugs reportados, etiquetar, escrever passos

Etiquetas a procurar nas issues: `good first issue`, `help wanted`, `documentation`.

---

## Dúvidas?

- Abre uma [issue](https://github.com/arseniomuanda/gestSchool/issues) com a etiqueta `question`
- Ou contacta o maintainer no GitHub

Obrigado por fazer o gestSchool melhor para todas as escolas que dele beneficiarão 🎓
