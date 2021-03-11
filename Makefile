##
## ----------------------------------------------------------------------------
##   ORIGAMI
## ----------------------------------------------------------------------------
##

# Checks if the GITHUB_ACTIONS environment variable is defined, useful for allowing the "--tty" flag locally but not in GitHub Actions.
export TTY := $(shell if [ -z "$${GITHUB_ACTIONS}" ]; then echo "--tty"; else echo ""; fi)

box: ## Compiles the project into a PHAR archive
	@composer dump-env prod
	@docker run --rm --interactive $${TTY} --volume="$$(pwd):/app" ajardin/humbug-box validate --verbose --ansi
	@docker run --rm --interactive $${TTY} --volume="$$(pwd):/app" ajardin/humbug-box compile --verbose --ansi
	@docker run --rm --interactive $${TTY} --volume="$$(pwd):/app" ajardin/humbug-box info --verbose --ansi
	@php build/origami.phar --version
	@rm .env.local.php
.PHONY: box

phpcsfixer-audit: ## Executes the code style analysis in dry-run mode on all PHP files
	PHP_CS_FIXER_IGNORE_ENV=1 ./vendor/bin/php-cs-fixer fix --dry-run --verbose --ansi
.PHONY: phpcsfixer

phpcsfixer-fix: ## Executes the code style analysis on all PHP files
	PHP_CS_FIXER_IGNORE_ENV=1 ./vendor/bin/php-cs-fixer fix --verbose --ansi
.PHONY: phpcsfixer

psalm: ## Executes a static analysis on all PHP files
	./vendor/bin/psalm --show-info=true --find-dead-code --no-cache
.PHONY: psalm

tests: ## Executes the unit tests and functional tests
	./bin/phpunit --testdox
.PHONY: tests

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'
.DEFAULT_GOAL := help
