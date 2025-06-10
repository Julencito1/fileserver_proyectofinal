FROM php:8.2-apache


RUN apt-get update && apt-get install -y unzip git curl \
    && a2enmod rewrite \
    && a2enmod php8.2 \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


WORKDIR /var/www/html


COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader


COPY . .


COPY ./config/000-default.conf /etc/apache2/sites-available/000-default.conf


EXPOSE 80
