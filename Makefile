##
## ----------------------------------------------------------------------------
##   ORIGAMI
## ----------------------------------------------------------------------------
##

# Checks if the GITHUB_ACTIONS environment variable is defined, useful for allowing the "--tty" flag locally but not in GitHub Actions.
export TTY := $(shell if [ -z "$${GITHUB_ACTIONS}" ]; then echo "--tty"; else echo ""; fi)

box: ## Compiles the project into a PHAR archive
	composer dump-env prod
	./bin/console cache:clear
	./bin/console cache:warmup
	docker run --rm --interactive $${TTY} --volume="$$(pwd):/app:delegated" ajardin/humbug-box compile -vvv
	rm .env.local.php
.PHONY: box

phpcsfixer-audit: ## Executes the code style analysis in dry-run mode on all PHP files
	docker run --rm --interactive $${TTY} --volume="$$(pwd):/app:delegated" ajardin/phpcsfixer fix --dry-run --verbose
.PHONY: phpcsfixer

phpcsfixer-fix: ## Executes the code style analysis on all PHP files
	docker run --rm --interactive $${TTY} --volume="$$(pwd):/app:delegated" ajardin/phpcsfixer fix --verbose
.PHONY: phpcsfixer

phpcpd: ## Executes a copy/paste analysis
	docker run --rm --interactive $${TTY} --volume="$$(pwd):/app:delegated" ajardin/phpcpd --fuzzy src tests
.PHONY: phpcpd

psalm: ## Executes a static analysis on all PHP files
	docker run --rm --interactive $${TTY} --volume="$$(pwd):/app:delegated" ajardin/psalm --show-info=true --find-dead-code
.PHONY: psalm

security: ## Executes a security audit on all PHP dependencies
	docker run --rm --interactive $${TTY} --volume="$$(pwd):/app:delegated" --workdir="/app" symfonycorp/cli check:security
.PHONY: security

tests: ## Executes the unit tests and functional tests
	./bin/phpunit --testdox
.PHONY: tests

update: ## Executes a Composer update within a PHP 7.3 environment
	docker run --rm --interactive $${TTY} --volume="$$(pwd):/var/www/html:delegated" ajardin/symfony-php:7.3 sh -c "composer update --optimize-autoloader"
.PHONY: update

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'
.DEFAULT_GOAL := help
