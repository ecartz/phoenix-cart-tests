#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CATALOG_REF="${PHOENIX_CATALOG_TAG:-$("$SCRIPT_DIR/read-catalog-pin.sh")}"
DEST="${PHOENIX_CART_ROOT:-$ROOT/PhoenixCart}"

if [[ -z "$CATALOG_REF" ]]; then
  echo "CE_PHOENIXCART_TAG is empty in fixtures/catalog_pin.txt" >&2
  exit 1
fi

if [[ -d "$DEST/.git" ]]; then
  git -C "$DEST" fetch --depth 1 origin "$CATALOG_REF" 2>/dev/null || git -C "$DEST" fetch --depth 1 origin
  git -C "$DEST" checkout --detach "$CATALOG_REF"
  exit 0
fi

rm -rf "$DEST"
git clone --depth 1 --branch "$CATALOG_REF" https://github.com/CE-PhoenixCart/PhoenixCart.git "$DEST"
