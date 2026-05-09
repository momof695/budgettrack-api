FROM php:8.2-cli

# Extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    curl zip unzip git libpq-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml bcmath

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Dossier de travail
WORKDIR /var/www

# Copier les fichiers
COPY . .

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chmod -R 775 storage bootstrap/cache

# Port
EXPOSE 8000

# Démarrage
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000