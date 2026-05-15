<h1 align="center">gestSchool</h1>

<p align="center">
  <strong>Tudo o que uma escola precisa para gerir um ano lectivo num único sistema</strong> — <br>
  matrículas, pautas, boletins, presenças e comunicados, sem licenças caras.
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.4+"></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind 4"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/licen%C3%A7a-Public%20v1.0-blue.svg" alt="gestSchool Public License v1.0"></a>
  <img src="https://img.shields.io/badge/source-available-orange.svg" alt="Source Available">
  <img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg" alt="PRs Welcome">
</p>

**TL;DR**
- 🏫 Para Ensino de Iniciação, Base e Médio (desenhado para Angola, adaptável a outros sistemas lusófonos)
- ⚙️ Laravel 13 + PHP 8.4 + Blade/Alpine + Tailwind 4 · SQLite por defeito
- 👥 7 perfis pré-configurados (Director, Pedagógico, Secretário, Professor, Assistente, Encarregado, Aluno)
- 🔓 Grátis para escolas públicas, IPSS, ONG e missões — uso comercial proibido
- 🚧 Em desenvolvimento activo — pronto para demo/staging

---

## Sobre

Cobre os cursos técnicos típicos do Ensino Médio angolano — Informática, Hotelaria, Contabilidade
e Gestão, GRH, Físicas e Biológicas — mas a estrutura é genérica e adaptável a outros sistemas
educativos lusófonos.

> ⚠️ **Quem pode usar:** escolas públicas e instituições educativas sem fins lucrativos (IPSS, ONG,
> missões, fundações). Utilização comercial, revenda e cobrança por serviços associados estão
> **expressamente proibidas**. Lê a [Licença](LICENSE) antes de usar.

## Funcionalidades

### Gestão de pessoas
- **Alunos**: ficha completa (BI, naturalidade, encarregados, histórico de matrículas)
- **Encarregados**: associação a múltiplos educandos com parentesco e responsável principal
- **Professores**: especialidade, habilitações, distinção entre titular e assistente
- **Funcionários**: secretaria, direcção pedagógica, direcção geral

### Estrutura académica
- **Anos lectivos** com trimestres configuráveis
- **Classes** (1ª-9ª Ensino Base, 10ª-13ª Ensino Médio) com nivelamento automático
- **Cursos** do ensino médio (com duração 3 ou 4 anos) ligados às classes que os compõem
- **Turmas** por classe + curso + ano lectivo, com sala, turno, capacidade e director de turma
- **Disciplinas** com currículo configurável por classe e curso

### Operação pedagógica
- **Matrículas** por aluno/turma/ano lectivo, com estado (activa, aprovado, reprovado, transferido, desistente)
- **Atribuições** professor × turma × disciplina × ano lectivo
- **Aulas** com sumário, conteúdo planeado e registo de presenças
- **Avaliações** por trimestre (prova, teste, avaliação contínua, exame) com peso configurável
- **Notas** por avaliação e matrícula
- **Pautas** por disciplina/trimestre, por turma/trimestre, e anuais
- **Boletim individual** por aluno
- **Comunicados** com alcance segmentado (todos, professores, encarregados, classe, turma)

### Acesso e segurança
- Autenticação via Laravel Breeze (login, registo, recuperação de password, verificação de email)
- 7 perfis pré-configurados via Spatie Permissions (ver tabela [abaixo](#perfis-de-acesso))
- Internacionalização: **Português (PT-AO)** e **Inglês**
- Logs de auditoria via timestamps em todas as entidades

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.4+, Laravel 13 |
| Frontend | Blade + Alpine.js + Tailwind CSS 4 (Vite) |
| Base de dados | SQLite por defeito (MySQL/PostgreSQL suportados) |
| Autenticação | Laravel Breeze |
| Permissões | Spatie Laravel Permission |
| Ícones | Blade Lucide Icons |

## Pré-requisitos

- PHP 8.4 ou superior com extensões: `pdo_sqlite`, `mbstring`, `xml`, `bcmath`, `intl`
- Composer 2.x
- Node.js 20+ e npm
- (opcional) MySQL 8+ ou PostgreSQL 14+ se quiseres mudar de SQLite

## Instalação rápida

```bash
# 1. Clonar
git clone https://github.com/arseniomuanda/gestSchool.git
cd gestSchool

# 2. Dependências
composer install
npm install

# 3. Configuração
cp .env.example .env
php artisan key:generate

# 4. Base de dados (SQLite por defeito)
touch database/database.sqlite
php artisan migrate --seed

# 5. Arrancar (servidor + queue + logs + vite num único comando)
composer run dev
```

A aplicação fica disponível em `http://localhost:8000`.

> Em alternativa ao `composer run dev`, podes correr `php artisan serve` e `npm run dev` em terminais separados.

### Configurar locale para Angola

No `.env`:

```env
APP_NAME="gestSchool"
APP_LOCALE=pt
APP_FALLBACK_LOCALE=pt
APP_FAKER_LOCALE=pt_PT
APP_TIMEZONE=Africa/Luanda
```

## Dados de exemplo

O comando `php artisan migrate --seed` (ou `migrate:fresh --seed` para começar do zero) gera uma escola
realista para testares a aplicação:

| Recurso | Quantidade |
|---|---|
| Anos lectivos | 5 (2022/2023 → 2026/2027, este último activo) |
| Classes | 13 (1ª-13ª) |
| Cursos | 5 (Informática, GRH, Hotelaria, Contabilidade e Gestão, Físicas e Biológicas) |
| Disciplinas | 32 |
| Turmas | ~290 distribuídas pelos 5 anos |
| Professores | 55 |
| Alunos únicos | ~3000 |
| Encarregados | ~2100 (alguns partilhados entre irmãos) |
| Matrículas | ~6700 (histórico de 5 anos) |
| Atribuições | ~2000 |
| Avaliações + notas | ~190 mil |
| Aulas + presenças | ~120 mil |

A geração demora cerca de 40 segundos em hardware moderno.

### Perfis de acesso

Todos os utilizadores demo usam a password **`password`**.

| Email | Perfil | O que pode fazer |
|---|---|---|
| `director@gestschool.test` | Director Geral | Acesso total |
| `pedagogico@gestschool.test` | Director Pedagógico | Gestão académica completa |
| `secretario@gestschool.test` | Secretário | Inscrições, matrículas, encarregados |
| `professor@gestschool.test` | Professor | Aulas, presenças, notas, avaliações |
| `assistente@gestschool.test` | Professor Assistente | Aulas, presenças (sem notas) |
| `encarregado@gestschool.test` | Encarregado | Boletim, presenças e comunicados dos seus educandos |
| `aluno@gestschool.test` | Aluno | Acesso de leitura ao próprio percurso |

## Estrutura do projecto

```
gestSchool/
├── app/
│   ├── Http/Controllers/         # Controladores REST (CRUD por recurso)
│   ├── Models/                   # 19 modelos Eloquent
│   └── Policies/                 # Autorização por recurso
├── database/
│   ├── migrations/               # 29 migrations
│   ├── seeders/
│   │   ├── AcademicSeeder.php          # estrutura: anos, classes, cursos, disciplinas
│   │   ├── ProfessoresSeeder.php       # 50 professores
│   │   ├── TurmasSeeder.php            # turmas por ano lectivo
│   │   ├── AlunosEncarregadosSeeder.php # 3000 alunos + encarregados
│   │   ├── AtribuicoesSeeder.php       # professores × turmas × disciplinas
│   │   └── PautasNotasPresencasSeeder.php # avaliações, notas, aulas, presenças
│   └── factories/
├── lang/                         # pt.json + en.json
├── resources/views/              # Blade templates organizados por recurso
└── routes/web.php
```

Para detalhes do schema, ver `database/migrations/` ou correr `php artisan model:show Aluno` (etc.).

## Comandos úteis

```bash
# Reseedar tudo do zero
php artisan migrate:fresh --seed

# Correr os testes (PHPUnit)
composer test

# Linter (Pint, padrão Laravel)
./vendor/bin/pint

# Mudar de SQLite para MySQL
# 1. comentar DB_CONNECTION=sqlite no .env
# 2. configurar DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD
# 3. php artisan migrate:fresh --seed
```

## Roadmap

- [x] CRUD completo de todas as entidades
- [x] Sistema de roles e permissões
- [x] Pautas e boletins
- [x] Seed realista com 5 anos lectivos
- [x] Exportação de pautas, boletins, horários e calendário em PDF
- [x] Calendário escolar visual (eventos com filtros, vista mensal, PDF)
- [x] Horários com bulk editor por turma e por professor (copy-paste, clipboard persistente, validação de conflitos)
- [x] Drag & drop no editor de horários (modo visual com SortableJS, toggle Form ↔ Visual)
- [ ] Sugestões automáticas de horário (heurísticas / lacunas)
- [ ] Notificações por email/SMS para encarregados
- [ ] Estatísticas e dashboards analíticos
- [ ] API REST pública para integrações
- [ ] App móvel para encarregados (Flutter)
- [ ] Gestão financeira (propinas, recibos)
- [ ] Suite de testes automatizados

## Como contribuir

Contribuições são muito bem-vindas — desde correcções de tradução até novas funcionalidades.

Lê o **[CONTRIBUTING.md](CONTRIBUTING.md)** para o guia completo: setup, padrões de código,
convenção de commits, fluxo de PR e áreas que precisam de ajuda.

Em resumo:

1. Faz fork do repositório
2. Cria uma branch: `git checkout -b feat/minha-feature`
3. Segue o padrão de código (Pint): `./vendor/bin/pint`
4. Faz commit com mensagens claras (preferencialmente em português)
5. Abre um Pull Request descrevendo o **porquê** da mudança

### Reportar bugs

Abre uma [issue](https://github.com/arseniomuanda/gestSchool/issues) com:
- Versão do PHP e do gestSchool (commit hash)
- Passos para reproduzir
- Comportamento esperado vs observado
- Screenshots quando aplicável

### Áreas que precisam de ajuda

- Tradução para outras línguas (Kimbundu, Umbundu, Francês)
- Documentação para administradores escolares
- Testes automatizados
- Acessibilidade (WCAG)

## Licença

Distribuído sob a **[Licença Pública gestSchool v1.0](LICENSE)** — uma licença *source-available*
desenhada para garantir que o sistema sirva primeiro quem mais precisa.

**O que podes fazer:**
- ✅ Usar gratuitamente em escolas públicas, IPSS, ONG, missões e outras instituições educativas sem fins lucrativos
- ✅ Estudar, modificar e adaptar o código às necessidades da tua instituição
- ✅ Distribuir cópias (originais ou modificadas) a outras instituições elegíveis
- ✅ Contribuir com melhorias via Pull Request

**O que não podes fazer:**
- ❌ Vender, sublicenciar ou alugar o software
- ❌ Oferecer como SaaS pago
- ❌ Cobrar por instalação, suporte, formação ou qualquer serviço relacionado
- ❌ Usar em escolas privadas com fins lucrativos
- ❌ Remover atribuições ao autor original

> **Roadmap da licença:** o autor pretende, em fases futuras, relicenciar o projecto sob uma licença
> mais permissiva (provavelmente MIT ou Apache 2.0) à medida que o ecossistema amadurece.
> Instituições não-elegíveis interessadas em usar o sistema podem solicitar licenciamento alternativo
> via [issue no repositório](https://github.com/arseniomuanda/gestSchool/issues/new).

> **Nota técnica:** esta licença não é uma licença open-source aprovada pela OSI por incluir
> restrições quanto a quem pode usar. É uma licença *source-available*: o código está aberto para
> consulta, modificação e contribuição.

## Autor

Desenvolvido por **Arsénio Muanda** — [@arseniomuanda](https://github.com/arseniomuanda)

Construído sobre o framework [Laravel](https://laravel.com).
