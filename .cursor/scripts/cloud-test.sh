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

if ! service mariadb status >/dev/null 2>&1; then
  service mariadb start
fi

exec composer test:all
