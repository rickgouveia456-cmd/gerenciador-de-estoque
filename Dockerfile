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
    && docker-php-ext-install pdo pdo_mysql gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# OPcache — cache de bytecode PHP (grande ganho de performance)
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# PHP producao — limites e seguranca
RUN echo "memory_limit=256M"         >> /usr/local/etc/php/conf.d/producao.ini \
    && echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/producao.ini \
    && echo "post_max_size=25M"       >> /usr/local/etc/php/conf.d/producao.ini \
    && echo "max_execution_time=60"   >> /usr/local/etc/php/conf.d/producao.ini \
    && echo "expose_php=Off"          >> /usr/local/etc/php/conf.d/producao.ini \
    && echo "display_errors=Off"      >> /usr/local/etc/php/conf.d/producao.ini \
    && echo "log_errors=On"           >> /usr/local/etc/php/conf.d/producao.ini \
    && echo "error_log=/var/log/apache2/php_errors.log" >> /usr/local/etc/php/conf.d/producao.ini

# Habilitar mod_rewrite e mod_headers para .htaccess e seguranca
RUN a2enmod rewrite headers expires

# Copiar configuracao do Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar arquivos PHP
COPY src/ /var/www/html/

# Instalar dependencias PHP (FPDF)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Permissoes corretas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && find /var/www/html -name "*.php" -exec chmod 644 {} \;

EXPOSE 80