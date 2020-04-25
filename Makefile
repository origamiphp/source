##
## ----------------------------------------------------------------------------
##   ORIGAMI
## ----------------------------------------------------------------------------
##

box: ## Compiles the project into a PHAR archive
	composer dump-env prod
	./bin/console cache:clear
	./bin/console cache:warmup
	docker run --interactive --volume="$$(pwd):/app" ajardin/humbug-box compile -vvv
	rm .env.local.php
.PHONY: box

phpcsfixer-audit: ## Fixes code style in all PHP files
	docker run --interactive --volume=$$(pwd):/app ajardin/phpcsfixer
.PHONY: phpcsfixer

phpcsfixer-fix: ## Fixes code style in all PHP files
	docker run --interactive --volume=$$(pwd):/app ajardin/phpcsfixer fix --verbose
.PHONY: phpcsfixer

phpcpd: ## Executes a copy/paste analysis
	docker run --interactive --volume=$$(pwd):/app ajardin/phpcpd
.PHONY: phpcpd

psalm: ## Executes a static analysis on all PHP files
	docker run --interactive --volume=$$(pwd):/app ajardin/psalm
.PHONY: psalm

security: ## Executes a security audit on all PHP dependencies
	docker run --interactive --volume=$$(pwd):/app ajardin/security-checker
.PHONY: security

tests: ## Executes the unit tests and functional tests
	./bin/phpunit --testdox
.PHONY: tests

update: ## Executes a Composer update within a PHP 7.3 environment
	docker run -it -v=$$(pwd):/var/www/html ajardin/symfony-php:7.3 sh -c "composer update --optimize-autoloader"
.PHONY: update

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'
.DEFAULT_GOAL := help
