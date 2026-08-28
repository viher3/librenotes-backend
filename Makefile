.PHONY: help

DOCKER_CLI = docker compose exec symfony-ddd-core-api-php
DOCKER_XDEBUG_CLI = docker exec -it symfony-ddd-core-api-php /bin/bash -c 'XDEBUG_TRIGGER=1 && export PHP_IDE_CONFIG="serverName=host.docker.internal" && bin/console $(command)'

help:
	@echo "########################"
	@echo "## Available commands ##"
	@echo "########################"
	@echo ""
	@echo '· docker/up'
	@echo '· docker/down'
	@echo '· docker/xdebug/enable'
	@echo '· docker/xdebug/disable'
	@echo ""
	@echo '· composer/install'
	@echo '· composer/update'
	@echo '· composer/dump-env/dev'
	@echo '· composer/dump-env/localdocker'
	@echo ''
	@echo '· jwt/generate-keys'
	@echo '· configuration/generate-encryption-key'
	@echo ''
	@echo '· doctrine/migrations/status'
	@echo '· doctrine/migrations/list'
	@echo '· doctrine/migrations/diff'
	@echo '· doctrine/migrations/migrate'
	@echo '· doctrine/migrations/generate'
	@echo '· doctrine/migrations/version-add'
	@echo '· doctrine/update/dump-sql'
	@echo '· doctrine/update/force-danger'
	@echo ''
	@echo '· cache/clear'
	@echo ''
	@echo '· phpstan/run'
	@echo '· phpstan/baseline'
	@echo ''
	@echo '· container/bash'
	@echo ''
	@echo '· debug/router'
	@echo ''
	@echo '· tests/acceptance/run'
	@echo '· tests/unit/run'
	@echo '· tests/cache/clear'
	@echo '· tests/db/reset'
	@echo ''

docker/up:
	docker compose up -d

docker/down:
	docker compose down

docker/xdebug/enable:
	bash ./etc/docker/bin/enable_xdebug.sh

docker/xdebug/disable:
	bash ./etc/docker/bin/disable_xdebug.sh

composer/install:
	$(DOCKER_CLI) composer install

composer/update:
	$(DOCKER_CLI) composer update

composer/dump-env/dev:
	$(DOCKER_CLI) composer dump-env dev

composer/dump-env/localdocker:
	$(DOCKER_CLI) composer dump-env localdocker

cache/clear:
	$(DOCKER_CLI) php bin/console cache:clear

container/bash:
	docker compose exec -ti symfony-ddd-core-api-php bash

debug/router:
	$(DOCKER_CLI) php bin/console debug:router

doctrine/migrations/status:
	$(DOCKER_CLI) php bin/console doctrine:migrations:status

doctrine/migrations/list:
	$(DOCKER_CLI) php bin/console doctrine:migrations:list

# Generates a migration from the diff between current mappings and the connected database.
# This is the normal way to turn an *.orm.xml change into a migration — review the generated
# SQL before committing it.
doctrine/migrations/diff:
	$(DOCKER_CLI) php bin/console doctrine:migrations:diff

doctrine/migrations/migrate:
	$(DOCKER_CLI) php bin/console doctrine:migrations:migrate

doctrine/migrations/generate:
	$(DOCKER_CLI) php bin/console doctrine:migrations:generate

# Marks all available migrations as executed WITHOUT running them. Only for a database whose
# schema already matches the mappings by some other means (e.g. this project's pre-migrations
# baseline) — never as a shortcut to skip reviewing a migration.
doctrine/migrations/version-add:
	$(DOCKER_CLI) php bin/console doctrine:migrations:version --add --all --no-interaction

doctrine/update/dump-sql:
	$(DOCKER_CLI) php bin/console doctrine:schema:update --dump-sql --complete

# Pushes the current mappings straight to the database, bypassing migrations entirely: no
# history, no review, no rollback. Kept only as a last-resort local debugging escape hatch —
# never use this to apply a real schema change; use doctrine/migrations/diff instead.
doctrine/update/force-danger:
	$(DOCKER_CLI) php bin/console doctrine:schema:update --force

linters/run:
	$(DOCKER_CLI) composer run-linters

phpstan/run:
	$(DOCKER_CLI) ./vendor/bin/phpstan analyse --memory-limit=1G

# Regenerates phpstan-baseline.neon, freezing today's remaining errors. Never run this to
# silence a NEW error introduced by your change — fix it instead. Only for widening scope
# (e.g. raising the level) or shrinking the baseline after fixing debt by hand.
phpstan/baseline:
	$(DOCKER_CLI) ./vendor/bin/phpstan analyse --memory-limit=1G --generate-baseline

tests/acceptance/run:
	$(DOCKER_CLI) ./vendor/bin/phpunit /app/tests/Acceptance

tests/unit/run:
	$(DOCKER_CLI) ./vendor/bin/phpunit --display-warnings /app/tests/Unit

tests/cache/clear:
	$(DOCKER_CLI) php bin/console cache:clear --env=test

# Recreates the test database from scratch via migrations. Run this after pulling a new
# migration, or whenever the test database's schema drifts from the test suite's expectations.
tests/db/reset:
	$(DOCKER_CLI) php bin/console doctrine:database:drop --force --if-exists --env=test
	$(DOCKER_CLI) php bin/console doctrine:database:create --env=test
	$(DOCKER_CLI) php bin/console doctrine:migrations:migrate -n --env=test

xdebug/cli:
	$(DOCKER_XDEBUG_CLI) $(command)

jwt/generate-keys:
	$(DOCKER_CLI) bash /app/bin/generate_jwt_keys.sh

# Prints a new base64-encoded 32-byte key. Add it to CONFIGURATION_ENCRYPTION_KEYS in
# .env.local under a new version number, bump CONFIGURATION_ENCRYPTION_KEY_CURRENT_VERSION,
# then run configuration:rotate-encryption-key — see the Configuration module's key rotation
# runbook before using this against a real incident, not just to provision a fresh environment.
configuration/generate-encryption-key:
	$(DOCKER_CLI) php -r "echo base64_encode(sodium_crypto_secretbox_keygen()), PHP_EOL;"

