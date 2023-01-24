CYPRESS_CACHE_FOLDER?=/e2e/.cache

build:
	docker build -f happy-lager-main/Dockerfile happy-lager-main -t happy-lager

down:
	docker-compose -f docker-compose.yml down --remove-orphans -v

clone:
	rm -rf happy-lager-main
	git clone https://github.com/craftcms/demo.git happy-lager-main
	cp -R happy-lager-override/* happy-lager-main

up: clone down
	rm -rf happy-lager-main/plugin-src
	mkdir happy-lager-main/plugin-src
	cp -R ../resources happy-lager-main/plugin-src/resources
	cp -R ../src happy-lager-main/plugin-src/src
	cp -R ../composer.json happy-lager-main/plugin-src/composer.json
	docker-compose -f docker-compose.yml up --build -d
	docker-compose exec -T mysql sh -c 'while ! mysqladmin ping -h"mysql" --silent; do sleep 1; done'
	docker-compose exec -T app sh -c 'php craft setup/security-key'
	docker-compose exec -T app sh -c 'echo DB_DRIVER=mysql >> .env'
	docker-compose exec -T app sh -c 'echo DB_SERVER=mysql >> .env'
	docker-compose exec -T app sh -c 'echo DB_DATABASE=craft-lilt >> .env'
	docker-compose exec -T app sh -c 'echo DB_USER=craft-lilt >> .env'
	docker-compose exec -T app sh -c 'echo DB_PASSWORD=craft-lilt >> .env'
	docker-compose exec -T app sh -c 'echo DB_SCHEMA=public >> .env'
	docker-compose exec -T app sh -c 'echo DB_TABLE_PREFIX= >> .env'
	docker-compose exec -T app sh -c 'php craft db/restore happylager.sql'
	docker-compose exec -T app sh -c 'php craft up'
	docker-compose exec -T app sh -c 'php craft migrate/up'
	docker-compose exec -T app sh -c 'php craft plugin/install craft-lilt-plugin'

cli:
	docker-compose exec -T app sh

test:
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} node:18.12.1 npm install
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER}
								  --network e2e-network \
								  --env-file .env.test \
  								  --entrypoint=cypress \
  								  cypress/included:12.2.0 run --headless --config-file /e2e/cypress.config.js --record false
