FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    nodejs \
    npm

RUN docker-php-ext-install pdo pdo_pgsql zip bcmath pcntl

RUN pecl install redis && docker-php-ext-enable redis

RUN pecl install pcov && docker-php-ext-enable pcov

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN npm install

COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

EXPOSE 8000 5173

CMD ["start"]
