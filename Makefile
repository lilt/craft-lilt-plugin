up:
	docker-compose up -d
	docker-compose exec mysql-test bash -c 'while ! mysqladmin ping -h"mysql-test" --silent; do sleep 1; done'

down:
	docker-compose down -v --remove-orphans

restart:
	docker-compose down
	docker-compose up -d

cli:
	docker-compose exec -u www-data cli-app sh

composer-install:
	docker-compose exec -T -u root cli-app sh -c "apk add git"
	docker-compose exec -T -u root cli-app sh -c "chown -R www-data:www-data /craft-lilt-plugin"
	docker-compose exec -T -u www-data cli-app sh -c "cp tests/.env.test tests/.env"
	docker-compose exec -T -u www-data cli-app sh -c "curl -s https://getcomposer.org/installer | php"
	docker-compose exec -T -u www-data cli-app sh -c "php composer.phar install"

quality: up
	docker-compose exec -T -u www-data cli-app sh -c "curl -L -s https://phar.phpunit.de/phpcpd.phar --output phpcpd.phar"
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/phpcs"
	docker-compose exec -T -u www-data cli-app sh -c "php phpcpd.phar src"

quality-fix:
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/phpcbf"

codecept-build:
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept build"

codecept-coverage:
	docker-compose exec -T -u www-data cli-app sh -c "php -dxdebug.mode=coverage vendor/bin/codecept run --coverage --coverage-xml --coverage-html"

integration: codecept-build
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run integration"

functional: codecept-build
	docker-compose exec -T -u www-data cli-app sh -c "php vendor/bin/codecept run functional"
