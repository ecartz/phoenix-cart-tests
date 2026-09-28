#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

: "${PHOENIX_DB_HOST:=127.0.0.1}"
: "${PHOENIX_INSTALLER_DB_NAME:=phoenix_install}"

mysql -h "$PHOENIX_DB_HOST" -u root <<SQL
DROP DATABASE IF EXISTS ${PHOENIX_INSTALLER_DB_NAME};
CREATE DATABASE ${PHOENIX_INSTALLER_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ${PHOENIX_INSTALLER_DB_NAME}.* TO 'phoenix'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "Reset database ${PHOENIX_INSTALLER_DB_NAME}."
