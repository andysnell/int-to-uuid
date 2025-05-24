# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli AS base
ENV COMPOSER_HOME="/app/build/composer"
ENV PATH="/app/bin:/app/vendor/bin:/app/build/composer/bin:$PATH"
ENV PHP_PEAR_PHP_BIN="php -d error_reporting=E_ALL&~E_DEPRECATED"
ENV XDEBUG_MODE="debug"
WORKDIR /app

RUN --mount=type=cache,target=/var/lib/apt --mount=type=tmpfs,target=/tmp/pear <<-EOF
  set -eux
  # Create a non-root user to run the application
  groupadd --gid 1000 dev
  useradd --uid 1000 --gid 1000 --groups www-data --shell /bin/bash dev
  chown -R 1000:1000 /app

  # Update and Install System Dependencies
  apt-get update
  apt-get dist-upgrade --yes
  apt-get install --yes --quiet --no-install-recommends git less unzip

  # Install Xdebug PHP Extension
  MAKEFLAGS="-j$(nproc --ignore=2)" pecl install xdebug
  docker-php-ext-enable xdebug

  # Configure PHP
  cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
  echo "memory_limit=1G" >> "$PHP_INI_DIR/php.ini"
  echo "assert.exception=1" >> "$PHP_INI_DIR/php.ini"
  echo "error_reporting=E_ALL" >> "$PHP_INI_DIR/php.ini"
  echo "display_errors=1" >> "$PHP_INI_DIR/php.ini"
  echo "log_errors=on" >> "$PHP_INI_DIR/php.ini"
  echo "xdebug.mode=debug" >> "$PHP_INI_DIR/php.ini"
  echo "xdebug.log_level=0" >> "$PHP_INI_DIR/php.ini"
EOF

FROM base AS development
COPY --link --from=composer/composer:latest-bin /composer /usr/bin/composer
USER dev

FROM base AS integration
COPY --link --from=composer/composer:latest-bin /composer /usr/bin/composer
COPY --link --chown=1000:1000 ./ /app/
USER dev
RUN mkdir "/app/build"
RUN --mount=type=cache,mode=0777,uid=1000,gid=1000,target=/app/build/composer/cache <<-EOF
    set -eux
    composer install --classmap-authoritative
EOF
