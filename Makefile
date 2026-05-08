UID := $(shell id -u)
GID := $(shell id -g)

PHP     = docker compose exec -T php
CONSOLE = $(PHP) php bin/console

.PHONY: setup start stop build restart shell migrate fixtures cache phpstan test help

.DEFAULT_GOAL := help

##@ 🚀 Setup

setup: ## Full setup from scratch — run this after cloning
	@echo "⚙️  Generating .env with your system UID/GID..."
	@printf "UID=%s\nGID=%s\n" "$(UID)" "$(GID)" > .env
	@echo "🐳 Starting containers..."
	@if [ -z "$$(docker compose ps -q)" ]; then \
		echo "   → First run: building images..."; \
		docker compose up -d --build --wait; \
	else \
		echo "   → Containers already running, skipping build."; \
		docker compose up -d --wait; \
	fi
	@echo "📦 Installing Composer dependencies..."
	@$(PHP) composer install --no-interaction --prefer-dist
	@echo "🗄️  Running migrations..."
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction
	@echo "🌱 Loading fixtures..."
	@$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "🧪 Setting up test database..."
	@docker compose exec -T mysql mysql -uroot -proot -e \
		"CREATE DATABASE IF NOT EXISTS symfony_test; \
		 GRANT ALL PRIVILEGES ON symfony_test.* TO 'developer'@'%'; \
		 FLUSH PRIVILEGES;" 2>/dev/null
	@$(CONSOLE) doctrine:migrations:migrate --env=test --no-interaction
	@echo "🔥 Warming up cache..."
	@$(CONSOLE) cache:warmup --quiet
	@echo ""
	@$(MAKE) phpstan
	@echo ""
	@$(MAKE) test
	@echo ""
	@echo "✅ Setup complete! Visit http://localhost"

##@ 🐳 Docker

start: ## Start all containers
	@docker compose up -d

stop: ## Stop all containers
	@docker compose down

build: ## Force rebuild all Docker images
	@docker compose up -d --build --wait

restart: stop start ## Restart all containers

shell: ## Enter PHP container shell
	@docker compose exec php bash

##@ ⚙️ Symfony

migrate: ## Run database migrations
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction

fixtures: ## Reload fixtures — ⚠️  resets all data
	@echo "⚠️  This will reset all data. Continue? [y/N] " && read ans && [ $${ans:-N} = y ]
	@$(CONSOLE) doctrine:fixtures:load --no-interaction

cache: ## Clear Symfony cache
	@$(CONSOLE) cache:clear

##@ 🔍 Quality

phpstan: ## Run PHPStan static analysis (level 5)
	@echo "🔍 Running PHPStan (level 5)..."
	@$(PHP) php -d memory_limit=512M vendor/bin/phpstan analyse

test: ## Run PHPUnit test suite
	@echo "🧪 Running PHPUnit tests..."
	@$(PHP) php bin/phpunit

##@ ℹ️  Help

help: ## Show available commands
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<command>\033[0m\n"} \
		/^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } \
		/^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' \
		$(MAKEFILE_LIST)