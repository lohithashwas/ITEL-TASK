FROM php:8.2-cli
RUN apt-get update && apt-get install -y libssl-dev libcurl4-openssl-dev pkg-config
RUN docker-php-ext-install mysqli pdo_mysql
RUN pecl install mongodb && docker-php-ext-enable mongodb
COPY . /app
WORKDIR /app
CMD sh -c "php -S 0.0.0.0:$PORT -t ."
