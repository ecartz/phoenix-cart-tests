#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

composer install --no-interaction

if [[ ! -f PhoenixCart/includes/system/autoloader.php ]]; then
  bash scripts/clone-catalog.sh
fi

# Warm Cloud VMs may boot from a snapshot older than `.cursor/Dockerfile` (no MariaDB yet).
if ! command -v mysql >/dev/null 2>&1 || ! dpkg -s mariadb-server >/dev/null 2>&1; then
  echo "Installing MariaDB and php-mysql for Cloud harness (image rebuild pending)." >&2
  apt-get update
  DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    mariadb-server mariadb-client php-mysql
fi

if ! service mariadb status >/dev/null 2>&1; then
  service mariadb start
fi

mysql -u root <<'SQL'
DROP DATABASE IF EXISTS phoenix_test;
CREATE DATABASE phoenix_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP DATABASE IF EXISTS phoenix_install;
CREATE DATABASE phoenix_install CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'phoenix'@'localhost' IDENTIFIED BY 'phoenix';
GRANT ALL PRIVILEGES ON phoenix_test.* TO 'phoenix'@'localhost';
GRANT ALL PRIVILEGES ON phoenix_install.* TO 'phoenix'@'localhost';
FLUSH PRIVILEGES;
SQL

export PHOENIX_DB_HOST=127.0.0.1
export PHOENIX_DB_PORT=3306
export PHOENIX_DB_NAME=phoenix_test
export PHOENIX_DB_USER=phoenix
export PHOENIX_DB_PASSWORD=phoenix
bash fixtures/import-mysql-fixtures.sh

if [[ -f package.json ]]; then
  npm ci --no-audit --no-fund
  npx playwright install-deps chromium
  npx playwright install chromium
fi

# Warm VMs may run before Cursor rebuilds .cursor/Dockerfile (Wave 7 needs Apache).
if ! command -v apache2ctl >/dev/null 2>&1 && ! command -v apachectl >/dev/null 2>&1; then
  echo "Installing apache2 and libapache2-mod-php for Wave 7 HTTPS (image rebuild pending)." >&2
  apt-get update
  DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    apache2 libapache2-mod-php openssl
  a2enmod ssl
  a2enmod php8.3
fi

if ! php -r 'exit(extension_loaded("curl") ? 0 : 1);'; then
  echo "Installing php-curl for Phoenix admin HTTP helpers (image rebuild pending)." >&2
  apt-get update
  DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends php-curl
fi

mkdir -p /run/lock/apache2 /var/run/apache2 2>/dev/null || true

ENV_FILE="$ROOT/.cursor/cloud.env"
cat > "$ENV_FILE" <<EOF
PHOENIX_MYSQL_ENABLED=1
PHOENIX_CART_ROOT=$ROOT/PhoenixCart
PHOENIX_DB_HOST=127.0.0.1
PHOENIX_DB_PORT=3306
PHOENIX_DB_NAME=phoenix_test
PHOENIX_DB_USER=phoenix
PHOENIX_DB_PASSWORD=phoenix
PHOENIX_HTTP_ENABLED=1
PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
PHOENIX_HTTP_HOST=127.0.0.1
PHOENIX_HTTP_PORT=8765
PHOENIX_BROWSER_ENABLED=1
PHOENIX_HTTPS_ENABLED=1
PHOENIX_HTTPS_BASE_URL=https://127.0.0.1:8443
EOF

echo "Cloud install complete. Run: composer cloud-test"
