# use a multi-stage build for dependencies
FROM composer as vendor
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer update --no-interaction --prefer-dist
RUN composer install --no-interaction --prefer-dist
RUN composer require lilt/craft-lilt-plugin:"dev-3.x-development"

FROM craftcms/nginx:8.0

# switch to the root user to install mysql tools
USER root
RUN apk add --no-cache mysql-client libpng libpng-dev libjpeg libjpeg-turbo freetype freetype-dev libjpeg-turbo-dev libzip-dev gd && docker-php-ext-configure gd --with-freetype --with-jpeg && apk del --no-cache libpng-dev freetype-dev libjpeg-turbo-dev
USER www-data

# the user is `www-data`, so we copy the files using the user and group
COPY --chown=www-data:www-data --from=vendor /app/vendor/ /app/vendor/
COPY --chown=www-data:www-data . .
