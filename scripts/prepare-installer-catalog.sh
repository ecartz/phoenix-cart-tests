#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

: "${PHOENIX_CART_ROOT:=$ROOT/PhoenixCart}"
: "${PHOENIX_INSTALLER_CATALOG_ROOT:=$ROOT/working/installer-catalog}"

if [[ ! -f "$PHOENIX_CART_ROOT/includes/system/autoloader.php" ]]; then
  echo "Missing catalog at PHOENIX_CART_ROOT=$PHOENIX_CART_ROOT" >&2
  exit 1
fi

rm -rf "$PHOENIX_INSTALLER_CATALOG_ROOT"
mkdir -p "$PHOENIX_INSTALLER_CATALOG_ROOT"

if command -v rsync >/dev/null 2>&1; then
  rsync -a --exclude '.git' "$PHOENIX_CART_ROOT/" "$PHOENIX_INSTALLER_CATALOG_ROOT/"
else
  cp -a "$PHOENIX_CART_ROOT/." "$PHOENIX_INSTALLER_CATALOG_ROOT/"
fi

rm -f "$PHOENIX_INSTALLER_CATALOG_ROOT/includes/local/configure.php"

echo "Prepared installer catalog at $PHOENIX_INSTALLER_CATALOG_ROOT"
