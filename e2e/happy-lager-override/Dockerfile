FROM craftcms/nginx:8.0

# switch to the root user to install mysql tools
USER root
RUN apk add --no-cache composer mysql-client libpng libpng-dev libjpeg libjpeg-turbo freetype freetype-dev libjpeg-turbo-dev libzip-dev gd && docker-php-ext-configure gd --with-freetype --with-jpeg && apk del --no-cache libpng-dev freetype-dev libjpeg-turbo-dev
USER www-data

# the user is `www-data`, so we copy the files using the user and group
COPY --chown=www-data:www-data . /app
COPY --chown=www-data:www-data ./plugin-src /tmp/craft-lilt-plugin
