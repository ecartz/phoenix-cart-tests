FROM ubuntu:24.04

# Bump when cloud image must rebuild (Cursor keys Dockerfile content).
ARG PHOENIX_CLOUD_ENV_REVISION=2026-09-26-playwright-install-deps

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    gnupg \
    mariadb-client \
    mariadb-server \
    php-cli \
    php-mbstring \
    php-mysql \
    php-xml \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Node 20 for Playwright (wave 5 browser tests in Cloud).
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
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
