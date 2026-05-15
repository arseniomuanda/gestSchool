.PHONY: up down build setup dev shell migrate fresh logs ps

# First-time bootstrap: build, install deps, key (if missing), migrate, then start dev
up:
	@test -d node_modules || { echo "❌ node_modules em falta. Corre 'npm install' no host primeiro (ver docs/DOCKER.md)."; exit 1; }
	docker compose up -d --build
	docker compose exec php composer install
	@docker compose exec php sh -c "[ -f .env ] || cp .env.example .env"
	@docker compose exec php sh -c "grep -q '^APP_KEY=base64:' .env || php artisan key:generate --ansi"
	docker compose exec php php artisan migrate --force
	docker compose exec php composer dev:docker

# Stop everything
down:
	docker compose down

# Rebuild images without cache
build:
	docker compose build --no-cache

# Just install deps + migrate (no dev runner)
setup:
	@test -d node_modules || { echo "❌ node_modules em falta. Corre 'npm install' no host primeiro."; exit 1; }
	docker compose exec php composer install
	docker compose exec php php artisan migrate --force

# Start the queue/pail/vite runner (foreground)
dev:
	docker compose exec php composer dev:docker

# Open a shell inside the php container
shell:
	docker compose exec php bash

# Run migrations
migrate:
	docker compose exec php php artisan migrate

# Drop and re-migrate everything
fresh:
	docker compose exec php php artisan migrate:fresh --seed

# Tail logs
logs:
	docker compose logs -f

# Show containers
ps:
	docker compose ps
