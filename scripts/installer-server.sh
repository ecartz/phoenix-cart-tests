#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

: "${PHOENIX_INSTALLER_CATALOG_ROOT:=$ROOT/working/installer-catalog}"
: "${PHOENIX_INSTALLER_HTTP_HOST:=127.0.0.1}"
: "${PHOENIX_INSTALLER_HTTP_PORT:=8766}"

if [[ ! -f "$PHOENIX_INSTALLER_CATALOG_ROOT/includes/system/autoloader.php" ]]; then
  echo "Missing installer catalog at PHOENIX_INSTALLER_CATALOG_ROOT=$PHOENIX_INSTALLER_CATALOG_ROOT" >&2
  echo "Run scripts/prepare-installer-catalog.sh or composer test:installer first." >&2
  exit 1
fi

ROUTER="$ROOT/scripts/php-built-in-router.php"
CAPTURE_MAIL="$ROOT/scripts/capture-installer-mail.php"
export PHOENIX_INSTALLER_MAIL_DIR="${PHOENIX_INSTALLER_MAIL_DIR:-$ROOT/working/installer-mail}"
export PHOENIX_MAIL_CAPTURE_DIR="$PHOENIX_INSTALLER_MAIL_DIR"
export PHOENIX_INSTALLER_MAIL_CAPTURE=1
mkdir -p "$PHOENIX_INSTALLER_MAIL_DIR"

echo "Serving installer catalog at http://${PHOENIX_INSTALLER_HTTP_HOST}:${PHOENIX_INSTALLER_HTTP_PORT}/"
echo "Installer mail capture: $PHOENIX_INSTALLER_MAIL_DIR"
cd "$PHOENIX_INSTALLER_CATALOG_ROOT"
exec php -d "sendmail_path=php $CAPTURE_MAIL -t -i" -S "${PHOENIX_INSTALLER_HTTP_HOST}:${PHOENIX_INSTALLER_HTTP_PORT}" -t . "$ROUTER"
