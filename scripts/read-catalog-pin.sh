#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PIN_FILE="$ROOT/fixtures/catalog_pin.txt"

if [[ ! -f "$PIN_FILE" ]]; then
  echo "Missing $PIN_FILE" >&2
  exit 1
fi

grep -E '^CE_PHOENIXCART_TAG=' "$PIN_FILE" | head -n 1 | cut -d= -f2- | tr -d '\r'
