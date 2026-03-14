FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip \
        git \
        libzip-dev \
        libbz2-dev \
        libicu-dev \
        libonig-dev \
        default-libmysqlclient-dev \
    && docker-php-ext-install \
        bcmath \
        bz2 \
        intl \
        opcache \
        pcntl \
        pdo \
        pdo_mysql \
        sockets \
        zip \
    && pecl install apcu && docker-php-ext-enable apcu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /checker

ENTRYPOINT ["php", "/checker/bin/composer-require-checker"]
CMD ["check"]
