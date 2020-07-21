##
## ----------------------------------------------------------------------------
##   ORIGAMI
## ----------------------------------------------------------------------------
##

box: ## Compiles the project into a PHAR archive
	composer dump-env prod
	./bin/console cache:clear
	./bin/console cache:warmup
	docker run --interactive --volume="$$(pwd):/app:delegated" ajardin/humbug-box compile -vvv
	rm .env.local.php
.PHONY: box

phpcsfixer-audit: ## Fixes code style in all PHP files
	docker run --interactive --volume="$$(pwd):/app:delegated" ajardin/phpcsfixer fix --dry-run --verbose
.PHONY: phpcsfixer

phpcsfixer-fix: ## Fixes code style in all PHP files
	docker run --interactive --volume="$$(pwd):/app:delegated" ajardin/phpcsfixer fix --verbose
.PHONY: phpcsfixer

phpcpd: ## Executes a copy/paste analysis
	docker run --interactive --volume="$$(pwd):/app:delegated" ajardin/phpcpd --fuzzy src tests
.PHONY: phpcpd

psalm: ## Executes a static analysis on all PHP files
	docker run --interactive --volume="$$(pwd):/app:delegated" ajardin/psalm --show-info=true --find-dead-code
.PHONY: psalm

security: ## Executes a security audit on all PHP dependencies
	docker run --interactive --volume="$$(pwd):/app:delegated" ajardin/security-checker security:check ./composer.lock
.PHONY: security

tests: ## Executes the unit tests and functional tests
	./bin/phpunit --testdox
.PHONY: tests

update: ## Executes a Composer update within a PHP 7.3 environment
	docker run -it -v="$$(pwd):/var/www/html:delegated" ajardin/symfony-php:7.3 sh -c "composer update --optimize-autoloader"
.PHONY: update

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'
.DEFAULT_GOAL := help
