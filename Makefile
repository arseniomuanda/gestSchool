.PHONY: start up down build setup dev shell migrate fresh logs ps tunnel tunnel-stop

# One-shot: bootstrap + runner. Equivalente a `make up && make dev`.
start: up dev

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

# Expose the app publicly via a Cloudflare quick tunnel (URL muda em cada arranque)
# Constrói assets primeiro (Vite HMR não funciona via tunnel) e mostra a URL pública.
tunnel:
	@echo "🔨 Build dos assets (Vite HMR não funciona em tunnel)…"
	docker compose exec -T php npm run build
	@docker compose exec -T php rm -f public/hot
	@echo "🚀 A arrancar Cloudflare Tunnel…"
	docker compose --profile tunnel up -d cloudflared
	@echo "⏳ À espera da URL pública…"
	@for i in 1 2 3 4 5 6 7 8 9 10; do \
		URL=$$(docker compose logs cloudflared 2>&1 | grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' | head -1); \
		if [ -n "$$URL" ]; then \
			echo ""; \
			echo "✅ Tunnel activo:"; \
			echo "   $$URL"; \
			echo ""; \
			echo "💡 Ajusta APP_URL no .env para essa URL e reinicia o php (docker compose restart php)"; \
			echo "   para evitar mixed-content e URLs absolutas mal geradas."; \
			echo ""; \
			echo "Para parar: make tunnel-stop"; \
			exit 0; \
		fi; \
		sleep 2; \
	done; \
	echo "⚠️  URL não apareceu em 20s — verifica 'docker compose logs cloudflared'"

# Pára o tunnel sem mexer no resto
tunnel-stop:
	docker compose --profile tunnel stop cloudflared
	docker compose --profile tunnel rm -f cloudflared
