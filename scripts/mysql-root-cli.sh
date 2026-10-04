#!/usr/bin/env bash
# Run mysql as superuser: TCP root password (CI), local socket root, or sudo socket (Cloud Agent).
set -euo pipefail

: "${PHOENIX_DB_HOST:=127.0.0.1}"
: "${PHOENIX_DB_PORT:=3306}"

if [[ -n "${PHOENIX_MYSQL_ROOT_PASSWORD:-}" ]]; then
  if mysql -h "$PHOENIX_DB_HOST" -P "$PHOENIX_DB_PORT" -u root -p"$PHOENIX_MYSQL_ROOT_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; then
    exec mysql -h "$PHOENIX_DB_HOST" -P "$PHOENIX_DB_PORT" -u root -p"$PHOENIX_MYSQL_ROOT_PASSWORD" "$@"
  fi
fi

if mysql -u root -e "SELECT 1" >/dev/null 2>&1; then
  exec mysql -u root "$@"
fi

if command -v sudo >/dev/null 2>&1 && sudo mysql -u root -e "SELECT 1" >/dev/null 2>&1; then
  exec sudo mysql "$@"
fi

echo "Cannot connect to MariaDB as root (set PHOENIX_MYSQL_ROOT_PASSWORD for TCP root)." >&2
exit 1
