FROM php:8.3-fpm AS base

LABEL authors="marca"

WORKDIR /var/www/html

RUN docker-php-ext-install pdo_mysql

FROM base AS dev

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip procps \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && { \
        echo "xdebug.mode=debug,develop"; \
        echo "xdebug.start_with_request=yes"; \
        echo "xdebug.client_host=host.docker.internal"; \
        echo "xdebug.client_port=9003"; \
      } > "$PHP_INI_DIR/conf.d/zz-xdebug.ini"

RUN groupadd --gid "${GID}" app \
    && useradd --uid "${UID}" --gid "${GID}" --create-home --shell /bin/bash app \
    && chown -R app:app /var/www/html

USER app

FROM base AS staging

#A remplir


FROM base AS prod

#A remplir après.