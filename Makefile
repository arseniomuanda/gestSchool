.PHONY: start demo up down build setup dev shell migrate fresh logs ps tunnel tunnel-stop backup backup-list backup-clean

# One-shot: bootstrap + runner. Equivalente a `make up && make dev`.
start: up dev

# One-shot: bootstrap + tunnel público. Equivalente a `make up && make tunnel`.
demo: up tunnel

.PHONY: up down build setup dev shell migrate fresh logs ps


# First-time bootstrap: build, install deps, key (if missing), migrate.
# Não inicia o runner — assim recriar containers (ex: alterar nginx) não mata o Vite.
up:
	docker compose up -d --build
	docker compose exec php composer install
	docker compose exec php npm install
	@docker compose exec php sh -c "[ -f .env ] || cp .env.example .env"
	@docker compose exec php sh -c "grep -q '^APP_KEY=base64:' .env || php artisan key:generate --ansi"
	docker compose exec php php artisan migrate --force
	@echo ""
	@echo "✅ Bootstrap completo. Próximo passo:"
	@echo "   make dev    # arranca queue + pail + vite (foreground, Ctrl+C para parar)"
	@echo ""
	@echo "App: http://localhost:8000 · pgAdmin: http://localhost:5050 · Mailpit: http://localhost:8025"

# Stop everything
down:
	docker compose down

# Rebuild images without cache
build:
	docker compose build --no-cache

# Just install deps + migrate (no dev runner)
setup:
	docker compose exec php composer install
	docker compose exec php npm install
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

# Corre um backup da DB manualmente (também corre auto às 02:00 via scheduler)
backup:
	docker compose exec -T php php artisan backup:run --only-db

# Lista todos os backups guardados (com tamanhos e datas)
backup-list:
	docker compose exec -T php php artisan backup:list

# Remove backups antigos segundo a política de retenção em config/backup.php
backup-clean:
	docker compose exec -T php php artisan backup:clean

# Expõe a app publicamente via ngrok (URL fixa em NGROK_DOMAIN no .env).
# Pára Vite dev, constrói assets estáticos (HMR não funciona via tunnel),
# arranca ngrok com a URL fixa, ajusta APP_URL e reinicia o PHP.
tunnel:
	@grep -q "^NGROK_DOMAIN=." .env || { echo "❌ NGROK_DOMAIN em falta no .env"; exit 1; }
	@grep -q "^NGROK_AUTHTOKEN=." .env || { echo "❌ NGROK_AUTHTOKEN em falta no .env"; exit 1; }
	@echo "🛑 A parar Vite dev (se estiver activo)…"
	@docker compose exec -T php pkill -f "concurrently|vite|queue:listen" 2>/dev/null || true
	@docker compose exec -T php rm -f public/hot
	@echo "🔨 Build dos assets…"
	docker compose exec -T php npm run build
	@echo "🚀 A arrancar ngrok…"
	@docker compose --profile tunnel rm -fs ngrok > /dev/null 2>&1 || true
	docker compose --profile tunnel up -d ngrok
	@URL="https://$$(grep '^NGROK_DOMAIN=' .env | cut -d= -f2)"; \
		sed -i.bak "s|^APP_URL=.*|APP_URL=$$URL|" .env; \
		rm -f .env.bak; \
		docker compose restart php > /dev/null; \
		sleep 3; \
		echo ""; \
		echo "✅ App: $$URL"; \
		echo ""; \
		echo "✏️  .env actualizado (APP_URL)"; \
		echo "Para parar: make tunnel-stop"

# Pára o tunnel e reverte APP_URL para localhost
tunnel-stop:
	docker compose --profile tunnel stop ngrok 2>&1 | tail -3
	docker compose --profile tunnel rm -f ngrok > /dev/null
	@sed -i.bak 's|^APP_URL=.*|APP_URL=http://localhost:8000|' .env
	@rm -f .env.bak
	@docker compose restart php > /dev/null 2>&1 || true
	@echo "✅ Tunnel parado. APP_URL revertido para http://localhost:8000"
