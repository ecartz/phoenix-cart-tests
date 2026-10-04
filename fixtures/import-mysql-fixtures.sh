#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

: "${PHOENIX_DB_HOST:=127.0.0.1}"
: "${PHOENIX_DB_PORT:=3306}"
: "${PHOENIX_DB_NAME:=phoenix_test}"
: "${PHOENIX_DB_USER:=phoenix}"
: "${PHOENIX_DB_PASSWORD:=phoenix}"

MYSQL=(mysql --default-character-set=utf8mb4 -h "$PHOENIX_DB_HOST" -P "$PHOENIX_DB_PORT" -u "$PHOENIX_DB_USER" -p"$PHOENIX_DB_PASSWORD" "$PHOENIX_DB_NAME")

"${MYSQL[@]}" < fixtures/phoenix.sql
"${MYSQL[@]}" < fixtures/phoenix_data_sample.sql
"${MYSQL[@]}" < fixtures/http/publish_info_pages.sql
"${MYSQL[@]}" < fixtures/http/enable_session_security_checks.sql
"${MYSQL[@]}" < fixtures/http/enable_newsletter_customer_data.sql
"${MYSQL[@]}" < fixtures/http/seed_customer.sql

if [[ "${PHOENIX_PAYMENT_SANDBOX_ENABLED:-0}" == "1" ]]; then
  php scripts/apply-payment-sandbox-config.php
fi

echo "Imported fixtures/phoenix.sql, fixtures/phoenix_data_sample.sql, and fixtures/http/*.sql into ${PHOENIX_DB_NAME}."
