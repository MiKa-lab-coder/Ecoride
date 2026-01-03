# Utilise l'image PHP officielle, basée sur Alpine, avec FPM
FROM php:8.2-fpm-alpine

# Arguments pour les versions des bibliothèques
ARG MONGODB_VERSION=1.17.0
ARG REDIS_VERSION=6.0.2

# Installe les dépendances système nécessaires pour les extensions PHP
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libxml2-dev \
    postgresql-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    build-base \
    autoconf \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev \
    freetype-dev \
    $PHPIZE_DEPS

# Installe les extensions PHP requises
RUN docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
    pdo_pgsql \
    dom \
    exif \
    xml \
    mbstring \
    gd \
    opcache \
    zip \
    bcmath

# Installe et active l'extension MongoDB
RUN pecl install mongodb && \
    docker-php-ext-enable mongodb

# Installe et active l'extension Redis
RUN pecl install redis-$REDIS_VERSION && \
    docker-php-ext-enable redis

# Installe Composer depuis le bon chemin
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Définit le répertoire de travail
WORKDIR /var/www/html

# Crée le répertoire de logs et définit les permissions
# L'utilisateur 'www-data' est l'utilisateur par défaut de PHP-FPM dans cette image Alpine
RUN mkdir -p logs && \
    chown -R www-data:www-data logs && \
    chmod -R 775 logs

# Expose le port de PHP-FPM
EXPOSE 9000

# Commande par défaut pour lancer PHP-FPM
CMD ["php-fpm"]