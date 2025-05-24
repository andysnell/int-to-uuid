SHELL := bash
.DEFAULT_GOAL := build

app = docker compose run --rm php

# Define behavior to safely source file (1) to dist file (2), without overwriting
# if the dist file already exists. This is more portable than using `cp --no-clobber`.
define copy-safe
	if [ ! -f "$(2)" ]; then \
		echo "Copying $(1) to $(2)"; \
		cp "$(1)" "$(2)"; \
	else \
		echo "$(2) already exists, not overwriting."; \
	fi
endef

# Define behavior to check if a token (1) is set in .env, and prompt user to set it if not.
# If the token is already set, inform the user. If the token name is not found in .env,
# it will be appended, otherwise, the existing value will be updated.
define check-token
	if grep -q "^$(1)=" ".env"; then \
		TOKEN_VALUE=$$(grep "^$(1)=" ".env" | cut -d'=' -f2); \
		if [ -z "$$TOKEN_VALUE" ]; then \
			read -p "Please enter your $(1): " NEW_TOKEN; \
			sed -i "s/^$(1)=.*/$(1)=$$NEW_TOKEN/" ".env"; \
			echo "$(1) updated successfully!"; \
		else \
			echo "$(1) is already set."; \
		fi; \
	else \
		read -p "$(1) not found. Please enter your $(1): " NEW_TOKEN; \
		echo -e "\n$(1)=$$NEW_TOKEN" >> ".env"; \
		echo "$(1) added successfully!"; \
	fi
endef

.env:
	@$(call copy-safe,.env.dist,.env)

# The build target dependencies must be set as "order-only" prerequisites to prevent
# the target from being rebuilt everytime the dependencies are updated.
build: | .env
	@$(call check-token,GITHUB_TOKEN)
	@docker compose build --pull
	@$(app) composer install
	@$(app) mkdir --parents build
	@touch build

.PHONY: vendor
vendor: build
	@$(app) composer install

.PHONY: clean
clean:
	$(app) -rf ./build ./vendor

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
phpbench:
	@$(app) vendor/bin/phpbench run --report=aggregate
