# Docker — Setup local

Setup completo para correr o gestSchool inteiramente dentro de Docker, com PHP 8.4,
PostgreSQL 18, Redis, Mailpit e pgAdmin.

Inspirado no template [refactorian/laravel-docker](https://github.com/refactorian/laravel-docker),
adaptado às versões e necessidades do projecto.

---

## TL;DR

```bash
make up
```

Faz tudo: build das imagens, instalação de dependências, migrations, e arranque do
runner de desenvolvimento (queue + pail + vite).

A app fica em **http://localhost**.

---

## Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (ou Docker Engine + Compose v2)
- `make` (pré-instalado em macOS e Linux)

Não precisas de PHP, Composer ou Node localmente — corre tudo dentro dos containers.

---

## Stack

| Serviço | Imagem | Porta (host) | Notas |
|---|---|---|---|
| `php` | build local (`php:8.4-fpm` + extensões) | 5173 | PHP-FPM, Composer, Node 22 |
| `nginx` | `nginx` | 80 | Serve a app em `http://localhost` |
| `db` | `postgres:18` | 5432 | DB `gestschool` / user `gestschool` / pass `gestschool` |
| `pgadmin` | `dpage/pgadmin4` | 5050 | UI web para o Postgres |
| `mail` | `axllent/mailpit:latest` | 8025 (UI), 1025 (SMTP) | Captura emails enviados pela app |
| `redis` | `redis:latest` | 6379 | Cache / sessões / queue |

### URLs

| Serviço | URL | Credenciais |
|---|---|---|
| App | http://localhost | (ver seeders, password `password`) |
| Vite HMR | http://localhost:5173 | — |
| pgAdmin | http://localhost:5050 | `admin@admin.com` / `password` |
| Mailpit UI | http://localhost:8025 | — |

### Extensões PHP incluídas

`pdo_pgsql`, `pgsql`, `pdo_sqlite`, `zip`, `intl`, `gd`, `redis`, `pcov`, `pcntl`.

> O `pdo_sqlite` está incluído para que possas correr o projecto contra SQLite caso
> queiras (basta mudar `DB_CONNECTION` no `.env`).

---

## Estrutura de ficheiros

```
.
├── docker-compose.yml                 # Definição dos serviços
├── Makefile                           # Atalhos para os comandos comuns
└── .docker/
    ├── php/
    │   ├── Dockerfile                 # PHP 8.4-fpm + extensões + Node 22 + Composer
    │   ├── php.ini                    # Overrides (timezone, upload limits)
    │   ├── docker.conf                # Config php-fpm
    │   ├── .bashrc                    # Aliases dentro do container
    │   └── entrypoint.sh              # Ajusta permissões em storage/ e bootstrap/cache
    ├── nginx/
    │   ├── default.conf               # Vhost que serve public/
    │   └── nginx.conf                 # Config global
    ├── db/
    │   ├── .gitignore                 # Ignora data/
    │   └── sql/                       # Scripts SQL executados na 1ª inicialização do Postgres
    ├── redis/
    │   └── .gitignore                 # Ignora data/
    └── logs/
        └── .gitignore
```

---

## Comandos disponíveis

Todos os comandos são corridos via `make` a partir da raiz do projecto.

### Bootstrap

```bash
make up
```

1. `docker compose up -d --build` — sobe (e constrói) todos os containers
2. `composer install` dentro do container `php`
3. `npm install` dentro do container `php`
4. Copia `.env.example` → `.env` (apenas se `.env` não existir)
5. Gera `APP_KEY` (apenas se ainda não houver uma)
6. `php artisan migrate --force`
7. Lança `composer dev:docker` — runner com queue, pail e vite em paralelo

### Operação diária

| Comando | O que faz |
|---|---|
| `make up` | Bootstrap completo (idempotente) |
| `make down` | Pára todos os containers |
| `make build` | Rebuilds completos sem cache |
| `make dev` | Relança o runner (queue + pail + vite) |
| `make shell` | Abre `bash` dentro do container `php` |
| `make migrate` | Corre `php artisan migrate` |
| `make fresh` | `php artisan migrate:fresh --seed` |
| `make logs` | Tail dos logs de todos os containers |
| `make ps` | Lista os containers em execução |
| `make setup` | Reinstala deps e corre migrations (sem dev runner) |

### Comandos directos

Para qualquer comando ad-hoc, usa `docker compose exec`:

```bash
docker compose exec php php artisan tinker
docker compose exec php php artisan make:controller Foo
docker compose exec php composer require foo/bar
docker compose exec php npm install some-package
docker compose exec db psql -U gestschool gestschool
```

---

## Configuração `.env`

O Docker compose injecta hostnames internos para cada serviço. As variáveis críticas
no `.env` devem ficar:

```env
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=db                 # nome do serviço, não 127.0.0.1
DB_PORT=5432
DB_DATABASE=gestschool
DB_USERNAME=gestschool
DB_PASSWORD=gestschool

REDIS_HOST=redis           # nome do serviço

MAIL_MAILER=smtp
MAIL_HOST=mail             # nome do serviço (Mailpit)
MAIL_PORT=1025
```

> Os hostnames `db`, `redis` e `mail` correspondem aos nomes dos serviços em
> `docker-compose.yml`. Dentro da rede do compose, esses nomes resolvem para os
> respectivos containers.

---

## Workflow de desenvolvimento

### Editar código

Os ficheiros são montados via volume (`.:/var/www`), portanto qualquer alteração
local é imediatamente visível dentro dos containers. Não precisas de rebuild para
mudanças em PHP, Blade, JS ou CSS.

### Vite HMR

O Vite está configurado para fazer bind em `0.0.0.0:5173` com HMR a apontar para
`localhost`, e usa polling para detectar alterações em volumes Docker:

```js
// vite.config.js
server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: { host: 'localhost' },
    watch: { usePolling: true },
}
```

Quando o `make dev` (ou `make up`) está a correr, o HMR funciona normalmente no browser.

### Queue e logs

O `composer dev:docker` corre três processos em paralelo:

- `php artisan queue:listen --tries=1 --timeout=0`
- `php artisan pail --timeout=0` — log viewer
- `npm run dev -- --host 0.0.0.0` — Vite

Para parar o runner: `Ctrl+C`. Os outros containers continuam a correr.

---

## Persistência de dados

| Volume | Localização local | Conteúdo |
|---|---|---|
| Postgres | `.docker/db/data/` | Ficheiros do Postgres |
| Redis | `.docker/redis/data/` | AOF do Redis |

Ambas estão no `.gitignore`. Para apagar os dados (e começar do zero):

```bash
make down
rm -rf .docker/db/data .docker/redis/data
make up
```

---

## Scripts SQL de inicialização

Qualquer ficheiro `.sql` ou `.sh` colocado em `.docker/db/sql/` é executado pelo
Postgres na **primeira** inicialização do container (quando `data/` está vazio).

Útil para:
- Criar utilizadores adicionais
- Criar bases de dados de teste (`gestschool_test`, etc.)
- Pré-carregar dados

---

## Troubleshooting

### Porta 80 já em uso

Se já tiveres algo no porto 80 (Apache, outro nginx, etc.), edita `docker-compose.yml`:

```yaml
nginx:
    ports:
        - 8080:80   # usa 8080 em vez de 80
```

Depois ajusta `APP_URL=http://localhost:8080` no `.env`.

### `composer dev:docker` não actualiza assets no browser

Verifica que estás a aceder a `http://localhost` (não `http://localhost:8000`) e que
o Vite está a correr (vê output do `make dev`). Se o HMR não funcionar, força refresh
(`Cmd+Shift+R`) — o `usePolling` pode demorar 1-2s em sistemas de ficheiros lentos.

### Permissões em `storage/` ou `bootstrap/cache/`

O `entrypoint.sh` faz `chown www-data:www-data` em ambos os directórios cada vez que
o container `php` arranca. Se vires erros de permissão, basta:

```bash
docker compose restart php
```

### Rebuild de uma extensão PHP

Editaste o `Dockerfile` para adicionar uma extensão? Rebuilds com:

```bash
docker compose build php
docker compose up -d
```

Para forçar um rebuild completo (sem cache):

```bash
make build
make up
```

### Limpar tudo e recomeçar

```bash
make down
docker compose down -v          # remove volumes anónimos também
rm -rf .docker/db/data .docker/redis/data
make up
```

---

## Diferenças vs template `refactorian/laravel-docker`

O template original assume MySQL; a nossa stack foi adaptada para:

- **Postgres 18** em vez de MySQL 8.1 (sem MySQL/MariaDB no compose)
- **pgAdmin 4** em vez de phpMyAdmin/Adminer
- **`pdo_sqlite`** adicionado (para compatibilidade com setup não-Docker do projecto)
- Removidos `pdo_mysql`, `npm install npm@latest -g` (causa erros) e `yarn` (não usado)
- Timezone PHP: `Africa/Luanda`
- Credenciais DB: `gestschool` (em vez de `refactorian`)
- Adicionado `Makefile` com atalho `make up` para bootstrap completo
- Adicionado `composer dev:docker` script (queue + pail + vite, sem `php artisan serve`)
- `vite.config.js` configurado para bind em `0.0.0.0` com polling

---

## Arrancar sem Docker

Se preferires correr sem Docker (PHP local + SQLite), segue a [Instalação rápida no README](../README.md#instalação-rápida).
Os dois setups são independentes — o `.env` é o único ficheiro que precisa de ser ajustado conforme o caminho escolhido.
