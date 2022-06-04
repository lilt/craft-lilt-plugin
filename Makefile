up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose down
	docker-compose up -d

cli:
	docker exec -it -u www-data craft-lilt-plugin-nginx sh

composer-install:
	docker-compose exec -T -u root nginx sh -c "apk add git"
	docker-compose exec -T -u root nginx sh -c "chown -R www-data:www-data /craft-lilt-plugin"
	docker-compose exec -T -u www-data nginx sh -c "cp tests/.env.test tests/.env"
	docker-compose exec -T -u www-data nginx sh -c "curl -s https://getcomposer.org/installer | php"
	docker-compose exec -T -u www-data nginx sh -c "php composer.phar install"

quality: up
	docker-compose exec -T -u www-data nginx sh -c "curl -L -s https://phar.phpunit.de/phpcpd.phar --output phpcpd.phar"
	docker-compose exec -T -u www-data nginx sh -c "php vendor/bin/phpcs"
	docker-compose exec -T -u www-data nginx sh -c "php phpcpd.phar src"

quality-fix:
	docker-compose exec -T -u www-data nginx sh -c "php vendor/bin/phpcbf"

codecept-build:
	docker-compose exec -T -u www-data nginx sh -c "php vendor/bin/codecept build"

codecept-coverage:
	docker-compose exec -T -u www-data nginx sh -c "php -dxdebug.mode=coverage vendor/bin/codecept run --coverage --coverage-xml --coverage-html"

integration: codecept-build
	docker-compose exec -T -u www-data nginx sh -c "php vendor/bin/codecept run integration"

functional: codecept-build
	docker-compose exec -T -u www-data nginx sh -c "php vendor/bin/codecept run functional"
