FROM php:8.2-apache

# Instalar extensoes necessarias
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite para .htaccess
RUN a2enmod rewrite

# Copiar configuracao do Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Diretorio de trabalho
WORKDIR /var/www/html

# Copiar arquivos PHP
COPY src/ /var/www/html/

# Instalar dependencias PHP (FPDF para PDF)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Permissoes
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
