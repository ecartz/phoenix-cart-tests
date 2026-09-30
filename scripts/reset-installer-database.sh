#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

: "${PHOENIX_INSTALLER_DB_NAME:=phoenix_install}"
: "${PHOENIX_DB_HOST:=127.0.0.1}"
: "${PHOENIX_DB_PORT:=3306}"

if [[ -n "${PHOENIX_MYSQL_ROOT_PASSWORD:-}" ]]; then
  mysql -h "$PHOENIX_DB_HOST" -P "$PHOENIX_DB_PORT" -u root -p"$PHOENIX_MYSQL_ROOT_PASSWORD" <<SQL
DROP DATABASE IF EXISTS ${PHOENIX_INSTALLER_DB_NAME};
CREATE DATABASE ${PHOENIX_INSTALLER_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ${PHOENIX_INSTALLER_DB_NAME}.* TO 'phoenix'@'%';
FLUSH PRIVILEGES;
SQL
else
  # MariaDB root on Ubuntu uses unix_socket auth; do not connect root via TCP (127.0.0.1).
  mysql -u root <<SQL
DROP DATABASE IF EXISTS ${PHOENIX_INSTALLER_DB_NAME};
CREATE DATABASE ${PHOENIX_INSTALLER_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ${PHOENIX_INSTALLER_DB_NAME}.* TO 'phoenix'@'localhost';
FLUSH PRIVILEGES;
SQL
fi

echo "Reset database ${PHOENIX_INSTALLER_DB_NAME}."
