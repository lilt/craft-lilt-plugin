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
	docker-compose exec -T -u www-data nginx sh -c "curl -s https://getcomposer.org/installer | php"
	docker-compose exec -T -u www-data nginx sh -c "php composer.phar install"

quality:
	docker-compose exec -T -u www-data nginx sh -c "php vendor/bin/phpcs"

quality-fix:
	docker-compose exec -T -u www-data nginx sh -c "php vendor/bin/phpcbf"
