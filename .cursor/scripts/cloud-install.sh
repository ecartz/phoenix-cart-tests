#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

composer install --no-interaction

if [[ ! -f PhoenixCart/includes/system/autoloader.php ]]; then
  git clone --depth 1 https://github.com/CE-PhoenixCart/PhoenixCart.git PhoenixCart
fi

if ! service mariadb status >/dev/null 2>&1; then
  service mariadb start
fi

mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS phoenix_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'phoenix'@'localhost' IDENTIFIED BY 'phoenix';
GRANT ALL PRIVILEGES ON phoenix_test.* TO 'phoenix'@'localhost';
FLUSH PRIVILEGES;
SQL

export PHOENIX_DB_HOST=127.0.0.1
export PHOENIX_DB_PORT=3306
export PHOENIX_DB_NAME=phoenix_test
export PHOENIX_DB_USER=phoenix
export PHOENIX_DB_PASSWORD=phoenix
bash fixtures/import-mysql-fixtures.sh

ENV_FILE="$ROOT/.cursor/cloud.env"
cat > "$ENV_FILE" <<EOF
PHOENIX_MYSQL_ENABLED=1
PHOENIX_CART_ROOT=$ROOT/PhoenixCart
PHOENIX_DB_HOST=127.0.0.1
PHOENIX_DB_PORT=3306
PHOENIX_DB_NAME=phoenix_test
PHOENIX_DB_USER=phoenix
PHOENIX_DB_PASSWORD=phoenix
EOF

echo "Cloud install complete. Run: composer cloud-test"
