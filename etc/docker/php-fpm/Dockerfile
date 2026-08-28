FROM php:8.4-fpm

RUN apt-get update && apt-get install -y zlib1g-dev libicu-dev git g++ libssl-dev \
    curl apt-utils zip libzip-dev gnupg2 libxml2-dev supervisor

RUN docker-php-ext-install pdo_mysql

# Install the necessary dependencies for gd and zip extensions
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# BCMath
RUN docker-php-ext-install bcmath

ENV TZ=Europe/Madrid
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "Europe/Madrid"\n' > /usr/local/etc/php/conf.d/tzone.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY etc/docker/php-fpm/conf/app.ini /usr/local/etc/php/conf.d/
COPY etc/docker/php-fpm/conf/xdebug.ini /usr/local/etc/php/conf.d/
COPY etc/docker/php-fpm/conf/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY etc/docker/php-fpm/entrypoint.sh /usr/local/bin/entrypoint
COPY etc/docker/php-fpm/supervisor/* /etc/supervisor/conf.d/

# COPY etc/docker/php-fpm/ssh/* /root/.ssh/
# RUN chmod 600 /root/.ssh/*

RUN mkdir -p /app/var/log/
RUN chmod -R 777 /app/var/

WORKDIR /app
COPY . ./

RUN chmod +x bin/console \
    && chmod +x /usr/local/bin/entrypoint


ENTRYPOINT [ "entrypoint" ]
CMD [ "php-fpm" ]
