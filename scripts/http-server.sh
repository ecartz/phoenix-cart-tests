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

ROUTER="$ROOT/scripts/php-built-in-router.php"
CAPTURE_MAIL="$ROOT/scripts/capture-installer-mail.php"
export PHOENIX_MAIL_CAPTURE_DIR="${PHOENIX_HTTP_MAIL_DIR:-$ROOT/working/http-mail}"
export PHOENIX_HTTP_MAIL_DIR="$PHOENIX_MAIL_CAPTURE_DIR"
export PHOENIX_HTTP_MAIL_CAPTURE=1
mkdir -p "$PHOENIX_MAIL_CAPTURE_DIR"

echo "Serving $PHOENIX_CART_ROOT at http://${PHOENIX_HTTP_HOST}:${PHOENIX_HTTP_PORT}/"
echo "HTTP mail capture: $PHOENIX_MAIL_CAPTURE_DIR"
cd "$PHOENIX_CART_ROOT"
exec php -d "sendmail_path=php $CAPTURE_MAIL -t -i" -S "${PHOENIX_HTTP_HOST}:${PHOENIX_HTTP_PORT}" -t . "$ROUTER"
