#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

: "${PHOENIX_DB_HOST:=127.0.0.1}"
: "${PHOENIX_DB_PORT:=3306}"
: "${PHOENIX_DB_USER:=phoenix}"
: "${PHOENIX_DB_PASSWORD:=phoenix}"
: "${PHOENIX_INSTALLER_DB_NAME:=phoenix_install}"

MYSQL=(mysql -h "$PHOENIX_DB_HOST" -P "$PHOENIX_DB_PORT" -u "$PHOENIX_DB_USER" -p"$PHOENIX_DB_PASSWORD")

"${MYSQL[@]}" -e "DROP DATABASE IF EXISTS \`${PHOENIX_INSTALLER_DB_NAME}\`;"
"${MYSQL[@]}" -e "CREATE DATABASE \`${PHOENIX_INSTALLER_DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "Reset database ${PHOENIX_INSTALLER_DB_NAME}."
