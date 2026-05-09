#!/usr/bin/env bash
set -e

# Installer Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Caches Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache