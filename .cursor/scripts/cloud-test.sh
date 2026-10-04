#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

ENV_FILE="$ROOT/.cursor/cloud.env"
if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing $ENV_FILE — run cloud install first (Cursor environment install or bash .cursor/scripts/cloud-install.sh)." >&2
  echo "If cloud-install exited early, the VM may lack MariaDB; re-run install after pulling latest main or rebuild the Cursor environment from .cursor/Dockerfile." >&2
  exit 1
fi

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

if ! command -v mysql >/dev/null 2>&1 || ! dpkg -s mariadb-server >/dev/null 2>&1; then
  echo "MariaDB is not installed — run bash .cursor/scripts/cloud-install.sh first." >&2
  exit 1
fi

if ! service mariadb status >/dev/null 2>&1; then
  service mariadb start
fi

mysql -u root <<'SQL'
DROP DATABASE IF EXISTS phoenix_test;
CREATE DATABASE phoenix_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL

bash fixtures/import-mysql-fixtures.sh

# Prefer image Node 24 over older nvm shims when both exist on warm Cloud VMs.
if [[ -x /usr/bin/node && -d /usr/lib/node_modules/npm ]]; then
  export PATH="/usr/bin:${PATH}"
fi

exec bash scripts/full-stack-test.sh
