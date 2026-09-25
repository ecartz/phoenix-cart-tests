FROM ubuntu:24.04

# Bump when cloud image must rebuild (Cursor keys Dockerfile content).
ARG PHOENIX_CLOUD_ENV_REVISION=2026-09-24-mariadb

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    mariadb-client \
    mariadb-server \
    php-cli \
    php-mbstring \
    php-mysql \
    php-xml \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Default socket auth for local root; phoenix user used by PHPUnit Integration tests.
RUN service mariadb start \
    && mysql -u root -e "CREATE DATABASE IF NOT EXISTS phoenix_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
CREATE USER IF NOT EXISTS 'phoenix'@'localhost' IDENTIFIED BY 'phoenix'; \
GRANT ALL PRIVILEGES ON phoenix_test.* TO 'phoenix'@'localhost'; \
FLUSH PRIVILEGES;" \
    && service mariadb stop
