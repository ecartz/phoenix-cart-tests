#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

read_catalog_pin() {
  local pin_file="$ROOT/fixtures/catalog_pin.txt"
  if [[ ! -f "$pin_file" ]]; then
    echo "Missing $pin_file" >&2
    exit 1
  fi
  grep -E '^CE_PHOENIXCART_TAG=' "$pin_file" | head -n 1 | cut -d= -f2- | tr -d '\r'
}

CATALOG_TAG="${PHOENIX_CATALOG_TAG:-}"
if [[ -z "$CATALOG_TAG" ]]; then
  CATALOG_TAG="$(read_catalog_pin)"
fi
if [[ -z "$CATALOG_TAG" ]]; then
  echo "Set PHOENIX_CATALOG_TAG or CE_PHOENIXCART_TAG in fixtures/catalog_pin.txt" >&2
  exit 1
fi

export PHOENIX_CART_ROOT="${PHOENIX_CART_ROOT:-$ROOT/PhoenixCart}"
export PHOENIX_MYSQL_ENABLED="${PHOENIX_MYSQL_ENABLED:-1}"
export PHOENIX_DB_HOST="${PHOENIX_DB_HOST:-127.0.0.1}"
export PHOENIX_DB_PORT="${PHOENIX_DB_PORT:-3306}"
export PHOENIX_DB_NAME="${PHOENIX_DB_NAME:-phoenix_test}"
export PHOENIX_DB_USER="${PHOENIX_DB_USER:-phoenix}"
export PHOENIX_DB_PASSWORD="${PHOENIX_DB_PASSWORD:-phoenix}"
export PHOENIX_HTTP_ENABLED="${PHOENIX_HTTP_ENABLED:-1}"
export PHOENIX_HTTP_BASE_URL="${PHOENIX_HTTP_BASE_URL:-http://127.0.0.1:8765}"
export PHOENIX_HTTP_HOST="${PHOENIX_HTTP_HOST:-127.0.0.1}"
export PHOENIX_HTTP_PORT="${PHOENIX_HTTP_PORT:-8765}"

echo "Release certification: CE-PhoenixCart tag ${CATALOG_TAG}"

if [[ -d "$PHOENIX_CART_ROOT/.git" ]]; then
  git -C "$PHOENIX_CART_ROOT" fetch --tags --depth 1 origin "refs/tags/${CATALOG_TAG}:refs/tags/${CATALOG_TAG}" 2>/dev/null \
    || git -C "$PHOENIX_CART_ROOT" fetch --tags origin
  git -C "$PHOENIX_CART_ROOT" checkout --detach "$CATALOG_TAG"
else
  rm -rf "$PHOENIX_CART_ROOT"
  git clone --depth 1 --branch "$CATALOG_TAG" https://github.com/CE-PhoenixCart/PhoenixCart.git "$PHOENIX_CART_ROOT"
fi

composer install --no-interaction

if command -v service >/dev/null 2>&1 && service mariadb status >/dev/null 2>&1; then
  :
elif command -v service >/dev/null 2>&1; then
  service mariadb start || true
fi

if command -v mysql >/dev/null 2>&1; then
  mysql_admin=(mysql -u root)
  if [[ -n "${MYSQL_ROOT_PASSWORD:-}" ]]; then
    mysql_admin+=(-p"${MYSQL_ROOT_PASSWORD}")
  fi
  "${mysql_admin[@]}" <<'SQL' 2>/dev/null || true
DROP DATABASE IF EXISTS phoenix_test;
CREATE DATABASE phoenix_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL
  bash fixtures/import-mysql-fixtures.sh
else
  echo "mysql client not found; set PHOENIX_MYSQL_ENABLED=0 only if Integration tests are skipped intentionally." >&2
fi

HTTP_PID=""
cleanup() {
  if [[ -n "$HTTP_PID" ]]; then
    kill "$HTTP_PID" 2>/dev/null || true
  fi
}
trap cleanup EXIT

if [[ "${PHOENIX_HTTP_ENABLED:-}" == "1" ]]; then
  bash scripts/http-server.sh &
  HTTP_PID=$!
  sleep 1
fi

composer test:stack
TEST_EXIT=$?

if [[ "$TEST_EXIT" -eq 0 && "${PHOENIX_BROWSER_ENABLED:-0}" == "1" && "${PHOENIX_HTTP_ENABLED:-0}" == "1" ]]; then
  if [[ -f package.json ]]; then
    if [[ ! -d node_modules/@playwright/test ]]; then
      npm ci --no-audit --no-fund
    fi
    npx playwright install-deps chromium
    npx playwright install chromium
    php scripts/write-http-local-configure.php
    composer test:browser
    TEST_EXIT=$?
  fi
fi

if [[ "$TEST_EXIT" -eq 0 ]]; then
  echo "Release certification passed for CE-PhoenixCart ${CATALOG_TAG}."
fi

exit "$TEST_EXIT"
