#!/usr/bin/make -f

CLIENT_SECRET := some-secret
CLIENT_CALLBACK := http://web.localhost:8080/rp/callback

DOCKER_COMPOSE_COMMAND := docker-compose

.PHONY: all
all: test

.PHONY: clean
clean:
	rm -rf ./build

.PHONY: clean-all
clean-all: clean
	rm -rf ./vendor
	rm -rf ./composer.lock

.PHONY: check
check:
	php vendor/bin/phpcs

.PHONY: test
test: clean check
	php vendor/bin/pest

.PHONY: coverage
coverage: test
	@if [ "`uname`" = "Darwin" ]; then open build/coverage/index.html; fi

.PHONY: up
up:
	$(DOCKER_COMPOSE_COMMAND) up -d
	$(DOCKER_COMPOSE_COMMAND) logs -f

.PHONY: down
down:
	$(DOCKER_COMPOSE_COMMAND) down -v

.PHONY: setup
setup:
	$(DOCKER_COMPOSE_COMMAND) exec hydra hydra create --format json --endpoint http://127.0.0.1:4445/ oauth2-client --skip-tls-verify \
		--secret ${CLIENT_SECRET} \
		--grant-type authorization_code \
		--grant-type refresh_token \
		--grant-type client_credential \
		--response-type code \
		--scope openid,offline_access \
		--token-endpoint-auth-method client_secret_basic \
		--redirect-uri ${CLIENT_CALLBACK}

.PHONY: teardown
teardown:
	$(DOCKER_COMPOSE_COMMAND) exec hydra hydra --endpoint http://127.0.0.1:4445/ clients --skip-tls-verify \
		delete ${CLIENT_ID}
