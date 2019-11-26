##
## ----------------------------------------------------------------------------
##   ORIGAMI
## ----------------------------------------------------------------------------
##

box: ## Compiles the project into a PHAR archive
	rm -rf ${HOME}/.origami/cache/* ${HOME}/.origami/logs/* var/*
	perl -pi -e "s/APP_ENV=dev/APP_ENV=prod/g" .env
	box compile
	perl -pi -e "s/APP_ENV=prod/APP_ENV=dev/g" .env
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

phpstan: ## Executes a static analysis at the higher level on all PHP files
	docker run --interactive --volume=$$(pwd):/app ajardin/phpstan
.PHONY: phpstan

security: ## Executes a security audit on all PHP dependencies
	docker run --interactive --volume=$$(pwd):/app ajardin/security-checker
.PHONY: security

tests: ## Executes the unit tests and functional tests
	./bin/console doctrine:database:drop --force --env=test
	./bin/console doctrine:database:create --env=test
	./bin/console doctrine:schema:create --env=test
	./bin/console doctrine:fixtures:load --no-interaction --env=test
	./bin/phpunit --testdox
.PHONY: tests

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' \
		| sed -e 's/\[32m##/[33m/'
.DEFAULT_GOAL := help
