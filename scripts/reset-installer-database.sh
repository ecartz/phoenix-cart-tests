#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

: "${PHOENIX_INSTALLER_DB_NAME:=phoenix_install}"
: "${PHOENIX_DB_HOST:=127.0.0.1}"
: "${PHOENIX_DB_PORT:=3306}"

MYSQL_ROOT="$ROOT/scripts/mysql-root-cli.sh"
PHOENIX_GRANT_HOST=localhost
if [[ -n "${PHOENIX_MYSQL_ROOT_PASSWORD:-}" ]] \
  && mysql -h "$PHOENIX_DB_HOST" -P "$PHOENIX_DB_PORT" -u root -p"$PHOENIX_MYSQL_ROOT_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; then
  PHOENIX_GRANT_HOST='%'
fi

"$MYSQL_ROOT" <<SQL
DROP DATABASE IF EXISTS ${PHOENIX_INSTALLER_DB_NAME};
CREATE DATABASE ${PHOENIX_INSTALLER_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ${PHOENIX_INSTALLER_DB_NAME}.* TO 'phoenix'@'${PHOENIX_GRANT_HOST}';
FLUSH PRIVILEGES;
SQL

echo "Reset database ${PHOENIX_INSTALLER_DB_NAME}."
