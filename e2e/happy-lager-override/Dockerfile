# use a multi-stage build for dependencies
FROM composer as vendor
COPY composer.json composer.json
COPY composer.lock composer.lock
COPY ./plugin-src /tmp/craft-lilt-plugin

RUN composer install --no-interaction
RUN composer require lilt/craft-lilt-plugin:^999.9.9
RUN composer require yiisoft/yii2:"<= 2.0.47"

#RUN chmod 755 /app
FROM craftcms/nginx:7.4

# switch to the root user to install mysql tools
USER root
RUN apk add --no-cache mysql-client libpng libpng-dev libjpeg libjpeg-turbo freetype freetype-dev libjpeg-turbo-dev libzip-dev gd && docker-php-ext-configure gd --with-freetype --with-jpeg && apk del --no-cache libpng-dev freetype-dev libjpeg-turbo-dev
USER www-data

# the user is `www-data`, so we copy the files using the user and group
COPY --chown=www-data:www-data --from=vendor /app/vendor/ /app/vendor/
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data ./plugin-src /tmp/craft-lilt-plugin
