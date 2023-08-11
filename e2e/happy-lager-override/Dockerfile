FROM craftcms/nginx:8.0

# switch to the root user to install mysql tools
USER root
RUN apk add --no-cache mysql-client libpng libpng-dev libjpeg libjpeg-turbo freetype freetype-dev libjpeg-turbo-dev libzip-dev gd && docker-php-ext-configure gd --with-freetype --with-jpeg && apk del --no-cache libpng-dev freetype-dev libjpeg-turbo-dev
