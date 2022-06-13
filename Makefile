# For .env file support uncomment next line
#include .env

export

PHP_VERSION?=7.2
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

tests-with-coverage: codecept-build install-pcov
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run --coverage-xml"

integration-with-coverage: codecept-build install-pcov
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run integration --coverage-xml --coverage-html"

integration: codecept-build
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run integration"

functional: codecept-build
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run functional"

unit: codecept-build
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run unit"

test: functional integration unit
