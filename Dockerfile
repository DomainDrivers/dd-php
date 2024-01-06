FROM php:8.3
RUN apt-get update -yqq
RUN apt-get install -y libzip-dev zip wget git procps
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
