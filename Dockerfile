FROM php:8.3-cli

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libsqlite3-dev \
	&& docker-php-ext-install pdo_mysql pdo_sqlite \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY . /var/www

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "/var/www/public", "/var/www/public/index.php"]
