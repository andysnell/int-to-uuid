SHELL := bash

dcr = docker compose run --rm php82
dcrx = docker compose run --rm php82-xdebug

.PHONY: build
build:
	@docker compose build
	@$(dcr) composer install

.PHONY: clean
clean:
	@$(dcr) rm -rf ./.phpbench
	@$(dcr) rm -rf ./.tmp
	@$(dcr) rm -rf ./vendor

.PHONY: shell
shell:
	@$(dcr) bash

.PHONY: composer
composer:
	@$(dcr) composer install

.PHONY: phpunit
phpunit:
	@$(dcr) vendor/bin/phpunit

.PHONY: phpbench
phpbench:
	@$(dcr) vendor/bin/phpbench run --report=aggregate

.PHONY: psysh
psysh:
	@$(dcrx) vendor/bin/psysh

.PHONY: phpcs
phpcs:
	@$(dcr) vendor/bin/phpcs

.PHONY: phpcs
phpcbf:
	@$(dcr) vendor/bin/phpcbf

.PHONY: phpstan
phpstan:
	@$(dcr) vendor/bin/phpstan
