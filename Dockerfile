FROM php:8.2-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        mariadb-server \
        mariadb-client \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        unzip \
        curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mysqli zip gd \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Apache: AllowOverride para .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

ENV DB_HOST=127.0.0.1 \
    DB_USER=ttkpro \
    DB_PASS=ttkpro \
    DB_NAME=ttkpro \
    MYSQL_ROOT_PASSWORD=rootpass

COPY . /var/www/html/
COPY docker/start.sh /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh \
    && chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/run/mysqld \
    && chown mysql:mysql /var/run/mysqld

EXPOSE 80
CMD ["/usr/local/bin/start.sh"]
