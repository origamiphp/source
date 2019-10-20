##
## ----------------------------------------------------------------------------
##   ORIGAMI
## ----------------------------------------------------------------------------
##

box: ## Compiles the project into a PHAR archive
	rm -rf ${HOME}/.origami/cache/* ${HOME}/.origami/logs/* var/*
	composer dump-env prod
	box compile
	rm .env.local.php
.PHONY: box

php-cs-fixer: ## Fixes code style in all PHP files
	./vendor/bin/php-cs-fixer fix --verbose
.PHONY: php-cs-fixer

phpcpd: ## Executes a copy/paste analysis
	./vendor/bin/phpcpd src tests
.PHONY: phpcpd

phpstan: ## Executes a static analysis at the higher level on all PHP files
	./vendor/bin/phpstan analyze src --level=max --memory-limit="-1" --verbose
	./vendor/bin/phpstan analyze tests --level=max --memory-limit="-1" --verbose
.PHONY: phpstan

security: ## Executes a security audit on all PHP dependencies
	bin/console security:check
.PHONY: security

tests: ## Executes the unit tests and functional tests
	bin/phpunit
.PHONY: tests

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'
.DEFAULT_GOAL := help
