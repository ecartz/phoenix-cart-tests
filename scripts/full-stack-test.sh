#!/usr/bin/env bash
# PHPUnit stack, optional Playwright, optional HTTPS — same phases as composer cloud-test.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

export PHOENIX_HTTP_ENABLED="${PHOENIX_HTTP_ENABLED:-1}"
export PHOENIX_HTTP_BASE_URL="${PHOENIX_HTTP_BASE_URL:-http://127.0.0.1:8765}"
export PHOENIX_HTTP_HOST="${PHOENIX_HTTP_HOST:-127.0.0.1}"
export PHOENIX_HTTP_PORT="${PHOENIX_HTTP_PORT:-8765}"
export PHOENIX_HTTPS_BASE_URL="${PHOENIX_HTTPS_BASE_URL:-https://127.0.0.1:8443}"

WANT_HTTPS="${PHOENIX_HTTPS_ENABLED:-0}"
unset PHOENIX_HTTPS_ENABLED

PHOENIX_HTTP_BASE_URL_PLAIN="${PHOENIX_HTTP_BASE_URL}"

if [[ ! -v PHOENIX_BROWSER_ENABLED ]]; then
  if [[ -f "$ROOT/package.json" ]] && command -v npx >/dev/null 2>&1; then
    export PHOENIX_BROWSER_ENABLED=1
  else
    export PHOENIX_BROWSER_ENABLED=0
  fi
fi

install_playwright_if_needed() {
  if [[ ! -d node_modules/@playwright/test ]]; then
    npm ci --no-audit --no-fund
  fi
  npx playwright install-deps chromium
  npx playwright install chromium
}

HTTP_PID=""
HTTPS_PID=""
cleanup() {
  if [[ -n "$HTTP_PID" ]]; then
    kill "$HTTP_PID" 2>/dev/null || true
  fi
  if [[ -n "$HTTPS_PID" ]]; then
    kill "$HTTPS_PID" 2>/dev/null || true
  fi
}
trap cleanup EXIT

if [[ "${PHOENIX_HTTP_ENABLED:-}" == "1" ]]; then
  if command -v fuser >/dev/null 2>&1; then
    fuser -k "${PHOENIX_HTTP_PORT}/tcp" 2>/dev/null || true
  fi
  bash scripts/http-server.sh &
  HTTP_PID=$!
  sleep 1
fi

composer test:stack
TEST_EXIT=$?

if [[ "$TEST_EXIT" -eq 0 && "${PHOENIX_BROWSER_ENABLED:-0}" == "1" && "${PHOENIX_HTTP_ENABLED:-0}" == "1" ]]; then
  install_playwright_if_needed
  php scripts/write-http-local-configure.php
  composer test:browser
  TEST_EXIT=$?
fi

if [[ "$TEST_EXIT" -eq 0 && "$WANT_HTTPS" == "1" ]]; then
  mkdir -p /run/lock/apache2 /var/run/apache2 2>/dev/null || true
  if command -v fuser >/dev/null 2>&1; then
    fuser -k "${PHOENIX_HTTPS_PORT:-8443}/tcp" 2>/dev/null || true
  fi
  bash scripts/https-server.sh &
  HTTPS_PID=$!
  sleep 2
  export PHOENIX_HTTPS_ENABLED=1
  export PHOENIX_HTTP_BASE_URL="${PHOENIX_HTTPS_BASE_URL}"
  composer test:https
  TEST_EXIT=$?
  export PHOENIX_HTTP_BASE_URL="${PHOENIX_HTTP_BASE_URL_PLAIN}"
fi

exit "$TEST_EXIT"
