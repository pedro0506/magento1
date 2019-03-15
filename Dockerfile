FROM php:7-apache

RUN apt-get update && apt-get install -y git unzip \
                                         libfreetype6-dev \
                                         libjpeg62-turbo-dev \
                                         libxml2-dev 

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
 && docker-php-ext-install pdo \
    pdo_mysql \
    gd \
    soap

RUN usermod -u 1001 www-data
RUN groupmod -g 1001 www-data

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/bin/composer && chmod +x /usr/bin/composer

RUN git clone https://github.com/OpenMage/magento-mirror.git /var/www/html
ADD ./ /var/www/html/
RUN chown -R www-data.www-data /var/www/html
RUN chmod -R ug+s /var/www/html

USER www-data
RUN composer install

USER root
