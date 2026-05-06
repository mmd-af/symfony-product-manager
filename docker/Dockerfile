FROM php:8.4-fpm
 
COPY --link \
    --from=ghcr.io/symfony-cli/symfony-cli:latest \
    /usr/local/bin/symfony /usr/local/bin/symfony
 
ARG UID=1000
ARG GID=1000
 
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    curl

RUN docker-php-ext-install zip pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ساخت یه user جدید به اسم symfony
# home directory رو /var/www قرار میدیم تا Composer مشکل permission نداشته باشه
RUN groupadd -g ${GID} --non-unique symfony && \
    useradd -u ${UID} -g symfony -G www-data -m -d /var/www symfony

RUN chown -R symfony:symfony /var/www

USER symfony

WORKDIR /var/www
 