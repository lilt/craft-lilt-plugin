CYPRESS_CACHE_FOLDER?=/e2e/.cache
DB_DATABASE?=craft-lilt

composer-install:
	docker run --rm \
		-v ${PWD}/happy-lager-main:/app \
		-v ${PWD}/happy-lager-main/plugin-src:/tmp/craft-lilt-plugin \
		composer:latest \
		composer require --ignore-platform-reqs -W "lilt/craft-lilt-plugin":"^999.9.9"

install: clone
	docker run --rm \
		-v ${PWD}/happy-lager-main:/app \
		-v ${PWD}/happy-lager-main/plugin-src:/tmp/craft-lilt-plugin \
		composer:latest \
		composer require craftcms/cms:3.7.68 -W

backup-db:
	docker-compose exec -T app sh -c 'php craft db/backup'

build:
	docker build -f happy-lager-main/Dockerfile happy-lager-main -t happy-lager

down:
	docker-compose down --remove-orphans -v

mup: #migrate app up
	docker-compose exec -T app sh -c 'php craft migrate/up'

mdown: #migrate app down
	docker-compose exec -T app sh -c 'php craft migrate/down'

cp:
	cp -R ../src happy-lager-main/plugin-src/src

clone:
	rm -rf happy-lager-main
	git clone https://github.com/craftcms/demo.git happy-lager-main
	rm -rf happy-lager-main/.git
	cp -R happy-lager-override/* happy-lager-main
	rm -rf happy-lager-main/plugin-src
	mkdir happy-lager-main/plugin-src
	cp -R ../resources happy-lager-main/plugin-src/resources
	cp -R ../src happy-lager-main/plugin-src/src
	cp -R ../composer.json happy-lager-main/plugin-src/composer.json

build: down clone composer-install
	docker-compose up --build -d

up: down
	docker-compose up -d
	docker-compose exec -T mysql sh -c 'while ! mysqladmin ping -h"mysql" --silent; do sleep 1; done'
	docker-compose exec -T app sh -c 'php craft setup/security-key'
	docker-compose exec -T app sh -c 'echo DB_DRIVER=mysql >> .env'
	docker-compose exec -T app sh -c 'echo DB_SERVER=mysql >> .env'
	docker-compose exec -T app sh -c 'echo DB_DATABASE=${DB_DATABASE} >> .env'
	docker-compose exec -T app sh -c 'echo DB_USER=craft-lilt >> .env'
	docker-compose exec -T app sh -c 'echo DB_PASSWORD=craft-lilt >> .env'
	docker-compose exec -T app sh -c 'echo DB_SCHEMA=public >> .env'
	docker-compose exec -T app sh -c 'echo DB_TABLE_PREFIX= >> .env'
	docker-compose exec -T app sh -c 'echo CRAFT_LILT_PLUGIN_QUEUE_DELAY_IN_SECONDS=5 >> .env'
	docker-compose exec -T app sh -c 'php craft db/restore happylager.sql'
	docker-compose exec -T app sh -c 'php craft plugin/install craft-lilt-plugin'
	docker-compose exec -T app sh -c 'php craft plugin/install neo'
	docker-compose exec -T app sh -c 'php craft plugin/install super-table'
	docker-compose exec -T app sh -c 'php craft project-config/rebuild'
	docker-compose exec -T app sh -c 'php craft up'
	docker-compose exec -T app sh -c 'php craft migrate/up'
	docker-compose exec -T app sh -c 'php craft queue/run'

cli:
	docker-compose exec -T app sh

test:
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} node:18.12.1 npm install
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} \
								  --network e2e-network \
								  --env-file .env.test \
  								  --entrypoint=cypress \
  								  cypress/included:12.2.0 run --headless --config-file /e2e/cypress.config.js --record false


test-instant:
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} node:18.12.1 npm install
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} \
								  --network e2e-network \
								  --env-file .env.test \
  								  --entrypoint=cypress \
  								  cypress/included:12.2.0 run --headless --config-file /e2e/cypress.config.js --spec cypress/e2e/jobs/instant/success-path-multiple.cy.js --record false

test-verified:
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} node:18.12.1 npm install
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} \
								  --network e2e-network \
								  --env-file .env.test \
  								  --entrypoint=cypress \
  								  cypress/included:12.2.0 run --headless --config-file /e2e/cypress.config.js --spec cypress/e2e/jobs/verified/*.js --record false

test-copy-source-text:
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} node:18.12.1 npm install
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} \
								  --network e2e-network \
								  --env-file .env.test \
  								  --entrypoint=cypress \
  								  cypress/included:12.2.0 run --headless --config-file /e2e/cypress.config.js --spec cypress/e2e/jobs/copy-source-text-flow/*.js --record false


# --ipc=host
# https://github.com/cypress-io/cypress/issues/350
e2e-github:
	docker run -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} node:18.12.1 npm install
	docker run --ipc=host -u root -t -v ${PWD}:/e2e -w /e2e --env CYPRESS_CACHE_FOLDER=${CYPRESS_CACHE_FOLDER} \
								  --network e2e-network \
								  --env-file .env.test \
  								  --entrypoint=cypress \
  								  cypress/included:12.2.0 run --headless --browser chrome --config-file /e2e/cypress.config.js --spec ${CYPRESS_SCENARIO}
