FROM ubuntu:24.04

# Bump when cloud image must rebuild (Cursor keys Dockerfile content).
ARG PHOENIX_CLOUD_ENV_REVISION=2026-09-28-cloud-node24

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    ca-certificates \
    curl \
    git \
    gnupg \
    libapache2-mod-php \
    mariadb-client \
    mariadb-server \
    openssl \
    php-cli \
    php-mbstring \
    php-mysql \
    php-xml \
    unzip \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod ssl \
    && a2enmod php8.3

# Node 24 for Playwright (align with GitHub Actions hosted runners).
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Chromium OS deps come from `npx playwright install-deps` during cloud-install (matches @playwright/test).

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Default socket auth for local root; phoenix user used by PHPUnit Integration tests.
RUN service mariadb start \
    && mysql -u root -e "CREATE DATABASE IF NOT EXISTS phoenix_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
CREATE USER IF NOT EXISTS 'phoenix'@'localhost' IDENTIFIED BY 'phoenix'; \
GRANT ALL PRIVILEGES ON phoenix_test.* TO 'phoenix'@'localhost'; \
FLUSH PRIVILEGES;" \
    && service mariadb stop
