FROM php:8.4-cli-alpine

RUN docker-php-ext-install pdo_mysql mysqli \
    && apk add --no-cache mariadb-client

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "/var/www/html", "/var/www/html/router.php"]
