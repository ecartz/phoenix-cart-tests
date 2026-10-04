#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ "${PHOENIX_MYSQL_ENABLED:-1}" != "1" ]]; then
  echo "PHOENIX_MYSQL_ENABLED is not 1; skipping fixture SQL verification." >&2
  exit 0
fi

export PHOENIX_DB_HOST="${PHOENIX_DB_HOST:-127.0.0.1}"
export PHOENIX_DB_PORT="${PHOENIX_DB_PORT:-3306}"
export PHOENIX_DB_NAME="${PHOENIX_DB_NAME:-phoenix_test}"
export PHOENIX_DB_USER="${PHOENIX_DB_USER:-phoenix}"
export PHOENIX_DB_PASSWORD="${PHOENIX_DB_PASSWORD:-phoenix}"

MYSQL=(mysql -h "$PHOENIX_DB_HOST" -P "$PHOENIX_DB_PORT" -u "$PHOENIX_DB_USER" -p"$PHOENIX_DB_PASSWORD" "$PHOENIX_DB_NAME")

query_scalar() {
  "${MYSQL[@]}" -N -e "$1" 2>/dev/null | head -n 1
}

products_count="$(query_scalar "SELECT COUNT(*) FROM products WHERE products_status = 1")"
if [[ -z "$products_count" || "$products_count" -lt 3 ]]; then
  echo "Expected at least 3 active products after fixture import; got: ${products_count:-<empty>}" >&2
  echo "Run: bash fixtures/import-mysql-fixtures.sh" >&2
  exit 1
fi

oranges="$(query_scalar "SELECT products_id FROM products WHERE products_id = 1")"
pears="$(query_scalar "SELECT products_id FROM products WHERE products_id = 3")"
if [[ "$oranges" != "1" || "$pears" != "3" ]]; then
  echo "Sample product ids 1 (Oranges) and 3 (Pears) missing; re-import fixtures/phoenix_data_sample.sql." >&2
  exit 1
fi

fixture_customer="$(query_scalar "SELECT customers_email_address FROM customers WHERE customers_email_address = 'phoenix-http-fixture@example.com' LIMIT 1")"
if [[ "$fixture_customer" != "phoenix-http-fixture@example.com" ]]; then
  echo "HTTP fixture customer missing; apply fixtures/http/seed_customer.sql via import-mysql-fixtures.sh." >&2
  exit 1
fi

pin_file="$ROOT/fixtures/catalog_pin.txt"
if [[ ! -f "$pin_file" ]]; then
  echo "Missing $pin_file" >&2
  exit 1
fi

echo "Fixture SQL verification passed (products=${products_count}, HTTP customer present, catalog pin file present)."
