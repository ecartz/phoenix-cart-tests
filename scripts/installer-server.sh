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

echo "Serving installer catalog at http://${PHOENIX_INSTALLER_HTTP_HOST}:${PHOENIX_INSTALLER_HTTP_PORT}/"
cd "$PHOENIX_INSTALLER_CATALOG_ROOT"
exec php -S "${PHOENIX_INSTALLER_HTTP_HOST}:${PHOENIX_INSTALLER_HTTP_PORT}" -t . "$ROUTER"
