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
    │   └── sql/                       # Scripts SQL executados na 1ª inicialização do Postgres
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

### Instalar dependências (npm / composer)

> ⚠️ **Importante:** corre `npm install` **fora** do container (no host).
>
> Quando o `npm install` corre dentro do container, o mount em `/var/www` faz com
> que o npm reescreva o `package-lock.json` com `"name": "www"` em vez de
> `"gestSchool"`. Isso polui o lockfile e cria diff espúrio em cada commit.
>
> Mesmo se não tiveres Node localmente, prefere instalar Node só para isto
> (via [nvm](https://github.com/nvm-sh/nvm) leva 30 segundos) — o container
> usa o `node_modules` resultante do mount.
>
> O `composer install` dentro do container é seguro e é o que `make up` faz.

```bash
# Host (correr no terminal normal)
npm install

# Container (já feito automaticamente pelo make up)
docker compose exec php composer install
```

Se já corrompeste o `package-lock.json` por engano:
```bash
git checkout -- package-lock.json
```

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

| Volume | Tipo | Conteúdo |
|---|---|---|
| `db_data` | named volume | Cluster Postgres |
| `redis_data` | named volume | AOF do Redis |

> **Porquê named volumes em vez de bind mounts?** No Docker Desktop para macOS, bind
> mounts vão pela camada de file sharing (VirtioFS/FUSE), que quebra `fsync()` e file
> locking. Com Postgres, isso traduz-se em corrupção mid-flight (`pg_filenode.map`
> desaparece). Named volumes ficam dentro da VM do Docker e usam ext4 directamente — é
> rápido e fiável. O trade-off é que os dados não são visíveis a partir do host.

Para apagar os dados (e começar do zero):

```bash
make down
docker compose down -v   # remove os named volumes
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

### `package-lock.json` ficou com `"name": "www"`

Aconteceu porque correste `npm install` dentro do container. Reverte:

```bash
git checkout -- package-lock.json
```

A partir daí, corre `npm install` apenas no host. Ver [Instalar dependências](#instalar-dependências-npm--composer).

### Modos de ficheiro mudam para `100755` sozinhos

Quando o entrypoint Docker corre `chmod`, o git detecta mudanças de mode em
ficheiros como `storage/*/.gitignore` que continuam a aparecer no `git status`
mesmo sem alteração de conteúdo. Fix permanente neste clone:

```bash
git config core.fileMode false
```

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
docker compose down -v   # remove containers + named volumes
make up
```

### Postgres dá `could not open file "global/pg_filenode.map"` ou shut down inesperado

Sintoma de bind mount em macOS. Confirma que o `docker-compose.yml` usa
`db_data:/var/lib/postgresql` (named volume) e **não** `.docker/db/data:/var/lib/postgresql`
(bind mount). Se acabaste de mudar, faz `docker compose down -v && make up` para
reinicializar com o named volume.

### PHP-FPM não arranca: `no listen address have been defined!`

O ficheiro `.docker/php/docker.conf` tem de declarar `listen = 9000` no bloco `[www]`
— senão o PHP-FPM 8.4+ falha a inicializar quando o nosso pool override esconde o
default do `www.conf`.

---

## Diferenças vs template `refactorian/laravel-docker`

O template original assume MySQL; a nossa stack foi adaptada para:

- **Postgres 18** em vez de MySQL 8.1 (sem MySQL/MariaDB no compose)
  - Mount em `/var/lib/postgresql` (não `/var/lib/postgresql/data`) — exigido pelo layout versionado do Postgres 18
  - **Named volume** `db_data` em vez de bind mount (bind mounts quebram Postgres em macOS)
- **pgAdmin 4** em vez de phpMyAdmin/Adminer
- **`pdo_sqlite`** adicionado (para compatibilidade com setup não-Docker do projecto)
- Removidos `pdo_mysql`, `npm install npm@latest -g` (causa erros) e `yarn` (não usado)
- `listen = 9000` adicionado ao `docker.conf` do PHP-FPM (necessário em PHP-FPM 8.4+)
- Timezone PHP: `Africa/Luanda`
- Credenciais DB: `gestschool` (em vez de `refactorian`)
- Adicionado `Makefile` com atalho `make up` para bootstrap completo
- Adicionado `composer dev:docker` script (queue + pail + vite, sem `php artisan serve`)
- `vite.config.js` configurado para bind em `0.0.0.0` com polling
- Redis também passou a usar named volume `redis_data`

---

## Arrancar sem Docker

Se preferires correr sem Docker (PHP local + SQLite), segue a [Instalação rápida no README](../README.md#instalação-rápida).
Os dois setups são independentes — o `.env` é o único ficheiro que precisa de ser ajustado conforme o caminho escolhido.
