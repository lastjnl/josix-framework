FROM php:8.3-cli

WORKDIR /var/www

COPY . /var/www

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "/var/www/public", "/var/www/public/index.php"]
