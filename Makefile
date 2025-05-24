SHELL := bash
.DEFAULT_GOAL := vendor

app = docker compose run --rm php

# The build target dependencies must be set as "order-only" prerequisites to prevent
# the target from being rebuilt everytime the dependencies are updated.
build:
	@docker compose build --pull
	@$(app) composer install
	@$(app) mkdir --parents build
	@touch build

.PHONY: vendor
vendor: build
	@$(app) composer install

.PHONY: clean
clean:
	$(app) rm -rf ./build ./vendor

.PHONY: up
up:
	docker compose up --detach

.PHONY: down
down:
	docker compose down --remove-orphans

.PHONY: bash
bash: build
	@$(app) bash

.PHONY: lint
lint: build
	@$(app) composer run-script lint

# Run tests, aliased to "phpunit" for consistency with other tooling targets.
.PHONY: test phpunit
phpunit: test
test: build
	@$(app) composer run-script test

# Generate HTML PHPUnit test coverage report, aliased to "phpunit-coverage" for consistency with other tooling targets.
.PHONY: test-coverage phpunit-coverage
phpunit-coverage: test-coverage
test-coverage: build
	@$(app) composer run-script test-coverage

# Run the PHP development server to serve the HTML test coverage report on port 8000.
.PHONY: serve-coverage
serve-coverage:
	@docker compose run --rm --publish 8000:80 php php -S 0.0.0.0:80 -t /app/build/phpunit

.PHONY: phpcs
phpcs: build
	@$(app) composer run-script phpcs

.PHONY: phpcbf
phpcbf: build
	@$(app) composer run-script phpcbf

.PHONY: phpstan
phpstan: build
	@$(app) composer run-script phpstan

.PHONY: rector
rector: build
	@$(app) composer run-script rector

.PHONY: rector-dry-run
rector-dry-run: build
	@$(app) composer run-script rector-dry-run

# Runs all the code quality checks: lint, phpstan, phpcs, and rector-dry-run".
.PHONY: ci
ci: build
	@$(app) composer run-script ci

# Runs the automated fixer tools, then run the code quality checks in one go, aliased to "preci".
.PHONY: pre-ci preci
preci: pre-ci
pre-ci: build phpcbf rector ci

.PHONY: phpbench
phpbench: build
	@$(app) vendor/bin/phpbench run --report=aggregate
