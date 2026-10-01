#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

export PHOENIX_INSTALLER_ENABLED=1
export PHOENIX_INSTALLER_BASE_URL="${PHOENIX_INSTALLER_BASE_URL:-http://127.0.0.1:8766}"
export PHOENIX_INSTALLER_MAIL_CAPTURE=1
export PHOENIX_INSTALLER_MAIL_DIR="${PHOENIX_INSTALLER_MAIL_DIR:-$ROOT/working/installer-mail}"

bash scripts/prepare-installer-catalog.sh
bash scripts/reset-installer-database.sh

bash scripts/installer-server.sh &
SERVER_PID=$!
trap 'kill "$SERVER_PID" 2>/dev/null || true' EXIT

for _ in $(seq 1 50); do
  if curl -sf -o /dev/null "${PHOENIX_INSTALLER_BASE_URL}/install/index.php" 2>/dev/null; then
    break
  fi
  sleep 0.2
done

vendor/bin/phpunit --testsuite installer
EXIT_CODE=$?

exit "$EXIT_CODE"
