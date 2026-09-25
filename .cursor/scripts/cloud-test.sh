#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

ENV_FILE="$ROOT/.cursor/cloud.env"
if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing $ENV_FILE — run cloud install first (Cursor environment install or bash .cursor/scripts/cloud-install.sh)." >&2
  exit 1
fi

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

# Warm Cloud VMs may keep an older cloud.env without HTTP acceptance vars.
export PHOENIX_HTTP_ENABLED="${PHOENIX_HTTP_ENABLED:-1}"
export PHOENIX_HTTP_BASE_URL="${PHOENIX_HTTP_BASE_URL:-http://127.0.0.1:8765}"
export PHOENIX_HTTP_HOST="${PHOENIX_HTTP_HOST:-127.0.0.1}"
export PHOENIX_HTTP_PORT="${PHOENIX_HTTP_PORT:-8765}"

if ! service mariadb status >/dev/null 2>&1; then
  service mariadb start
fi

mysql -u root <<'SQL'
DROP DATABASE IF EXISTS phoenix_test;
CREATE DATABASE phoenix_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL

bash fixtures/import-mysql-fixtures.sh

HTTP_PID=""
cleanup() {
  if [[ -n "$HTTP_PID" ]]; then
    kill "$HTTP_PID" 2>/dev/null || true
  fi
}
trap cleanup EXIT

if [[ "${PHOENIX_HTTP_ENABLED:-}" == "1" ]]; then
  bash scripts/http-server.sh &
  HTTP_PID=$!
  sleep 1
fi

composer test:all
exit $?
