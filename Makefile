# For .env file support uncomment next line
#include .env

export

PHP_VERSION?=8.1
MYSQL_VERSION?=5.7

up:
	docker-compose up -d
	docker-compose exec -T mysql-test sh -c 'while ! mysqladmin ping -h"mysql-test" --silent; do sleep 1; done'

down:
	docker-compose down -v --remove-orphans

restart: down
	docker-compose up -d

cli:
	docker-compose exec -u www-data cli-app sh

root:
	docker-compose exec -u root cli-app sh

composer-install:
	docker-compose exec -T -u root cli-app sh -c "apk add git"
	docker-compose exec -T -u root cli-app sh -c "chown -R www-data:www-data /craft-lilt-plugin"
	docker-compose exec -T -u root cli-app sh -c "rm -f composer.lock"
	docker-compose exec -T -u root cli-app sh -c "rm -rf vendor"
	docker-compose exec -T -u www-data cli-app sh -c "cp tests/.env.test tests/.env"
	docker-compose exec -T -u www-data cli-app sh -c "curl -s https://getcomposer.org/installer | php"
	docker-compose exec -T -u www-data cli-app sh -c "php composer.phar install"

quality:
	docker-compose exec -T -u www-data cli-app sh -c "curl -L -s https://phar.phpunit.de/phpcpd.phar --output phpcpd.phar"
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/phpcs"
	docker-compose exec -T -u www-data cli-app sh -c "php phpcpd.phar src"

quality-fix:
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/phpcbf"

codecept-build:
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept build"

coverage-xdebug:
	docker-compose exec -T -u www-data cli-app sh -c "php -dxdebug.mode=coverage vendor/bin/codecept run --coverage --coverage-xml --coverage-html"

install-pcov:
	docker-compose exec -T -u root cli-app sh -c "apk --no-cache add pcre-dev autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c"
	docker-compose exec -T -u root cli-app sh -c "pecl install pcov || true"
	docker-compose exec -T -u root cli-app sh -c "docker-php-ext-enable pcov"

coverage: install-pcov
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run --coverage --coverage-xml --coverage-html"

tests-with-coverage: codecept-build install-pcov unit-coverage integration-coverage functional-coverage

integration-coverage:
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run integration --coverage-xml=coverage-integration.xml"

functional-coverage:
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run functional --coverage-xml=coverage-functional.xml"

unit-coverage:
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run unit --coverage-xml=coverage-unit.xml"

integration: codecept-build
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run integration"

functional: codecept-build
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run functional"

unit: codecept-build
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run unit"

test: functional integration unit

prepare-container:
	PHP_VERSION=7.2 docker-compose up -d
	docker-compose exec -T -u root cli-app sh -c "chown -R www-data:www-data /craft-lilt-plugin"
	docker-compose exec -T -u root cli-app sh -c "apk --no-cache add bash make git"
	docker-compose exec -T -u www-data cli-app sh -c "cp tests/.env.test tests/.env"
	docker-compose exec -T -u root cli-app sh -c "curl -s https://getcomposer.org/installer | php"
	docker-compose exec -T -u root cli-app sh -c "cp composer.phar /bin/composer"

test-craft-versions-1: prepare-container
	docker-compose exec -T -u www-data cli-app bash -c \
		"./craft-versions.sh 3.7.0 3.7.1 3.7.2 3.7.3.1 3.7.3.2 3.7.3 3.7.4 3.7.5 3.7.6 3.7.8"

test-craft-versions-2: prepare-container
	docker-compose exec -T -u www-data cli-app bash -c \
		"./craft-versions.sh 3.7.9 3.7.10 3.7.11 3.7.12 3.7.13 3.7.14 3.7.15 3.7.16 3.7.17 3.7.17.1"

test-craft-versions-3: prepare-container
	docker-compose exec -T -u www-data cli-app bash -c \
		"./craft-versions.sh 3.7.17.2 3.7.18 3.7.18.1 3.7.18.2 3.7.19 3.7.19.1 3.7.20 3.7.21 3.7.22 3.7.23"

test-craft-versions-4: prepare-container
	docker-compose exec -T -u www-data cli-app bash -c \
		"./craft-versions.sh 3.7.24 3.7.25 3.7.25.1 3.7.26 3.7.27 3.7.27.1 3.7.27.2 3.7.28 3.7.29 3.7.30"

test-craft-versions-5: prepare-container
	docker-compose exec -T -u www-data cli-app bash -c \
		"./craft-versions.sh 3.7.30.1 3.7.31 3.7.32 3.7.33 3.7.34 3.7.35 3.7.36 3.7.37 3.7.38 3.7.39"

test-craft-versions-6: prepare-container
	docker-compose exec -T -u www-data cli-app bash -c \
		"./craft-versions.sh 3.7.40 3.7.40.1 3.7.41 3.7.42 3.7.43 3.7.44"