##
## ----------------------------------------------------------------------------
##   ORIGAMI
## ----------------------------------------------------------------------------
##

# Checks if the GITHUB_ACTIONS environment variable is defined, useful for allowing the "--tty" flag locally but not in GitHub Actions.
export TTY := $(shell if [ -z "$${GITHUB_ACTIONS}" ]; then echo "--tty"; else echo ""; fi)

box: ## Compiles the project into a PHAR archive
	@composer dump-env prod
	@box validate --verbose --ansi
	@box compile --verbose --ansi
	@box info --verbose --ansi
	@php build/origami.phar --version
	@rm .env.local.php
.PHONY: box

ci: ## Executes all the Continuous Integration tests
	make composer lint phpcsfixer-audit phpstan psalm phpunit rector-audit
.PHONY: ci

composer: ## Executes the analysis on the Composer files
	composer validate --strict
.PHONY: composer

lint: ## Executes the Symfony linters on configuration files and the container
	./bin/console lint:yaml config/
	./bin/console lint:container
.PHONY: lint

phpcsfixer-audit: ## Executes the code style analysis in dry-run mode on all PHP files
	./vendor/bin/php-cs-fixer fix --dry-run --verbose --ansi
.PHONY: phpcsfixer-audit

phpcsfixer-fix: ## Executes the code style analysis on all PHP files
	./vendor/bin/php-cs-fixer fix --verbose --ansi
.PHONY: phpcsfixer-fix

phpstan: ## Executes the static analysis on all PHP files with PHPStan
	./vendor/bin/phpstan analyse
.PHONY: phpstan

phpunit: ## Executes the unit and functional tests
	./bin/phpunit --testdox
.PHONY: phpunit

psalm: ## Executes the static analysis on all PHP files with Psalm
	./vendor/bin/psalm --show-info=true --find-dead-code --stats --shepherd
.PHONY: psalm

rector-audit: ## Executes the automated refactoring in dry-run mode on all PHP files
	./vendor/bin/rector process --dry-run
.PHONY: rector-audit

rector-fix: ## Executes the automated refactoring on all PHP files
	./vendor/bin/rector process
.PHONY: rector-fix

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'
.DEFAULT_GOAL := help
