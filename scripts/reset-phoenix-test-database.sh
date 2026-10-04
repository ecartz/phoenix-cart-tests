#!/usr/bin/env bash
# Drop and recreate phoenix_test before fixture import (warm Cloud VMs, local re-runs).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

: "${PHOENIX_DB_HOST:=127.0.0.1}"
: "${PHOENIX_DB_NAME:=phoenix_test}"

"$ROOT/scripts/mysql-root-cli.sh" <<SQL
DROP DATABASE IF EXISTS ${PHOENIX_DB_NAME};
CREATE DATABASE ${PHOENIX_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL

export PHOENIX_DB_HOST PHOENIX_DB_NAME
export PHOENIX_DB_PORT="${PHOENIX_DB_PORT:-3306}"
export PHOENIX_DB_USER="${PHOENIX_DB_USER:-phoenix}"
export PHOENIX_DB_PASSWORD="${PHOENIX_DB_PASSWORD:-phoenix}"

bash fixtures/import-mysql-fixtures.sh
