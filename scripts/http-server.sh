#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

: "${PHOENIX_CART_ROOT:=$ROOT/PhoenixCart}"
: "${PHOENIX_HTTP_HOST:=127.0.0.1}"
: "${PHOENIX_HTTP_PORT:=8765}"

if [[ ! -f "$PHOENIX_CART_ROOT/includes/system/autoloader.php" ]]; then
  echo "Missing catalog at PHOENIX_CART_ROOT=$PHOENIX_CART_ROOT" >&2
  exit 1
fi

echo "Serving $PHOENIX_CART_ROOT at http://${PHOENIX_HTTP_HOST}:${PHOENIX_HTTP_PORT}/"
cd "$PHOENIX_CART_ROOT"
exec php -S "${PHOENIX_HTTP_HOST}:${PHOENIX_HTTP_PORT}" -t .
