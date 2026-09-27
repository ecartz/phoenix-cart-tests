#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

: "${PHOENIX_CART_ROOT:=$ROOT/PhoenixCart}"
: "${PHOENIX_HTTPS_HOST:=127.0.0.1}"
: "${PHOENIX_HTTPS_PORT:=8443}"
: "${PHOENIX_DB_HOST:=127.0.0.1}"
: "${PHOENIX_DB_PORT:=3306}"
: "${PHOENIX_DB_NAME:=phoenix_test}"
: "${PHOENIX_DB_USER:=phoenix}"
: "${PHOENIX_DB_PASSWORD:=phoenix}"

if [[ ! -f "$PHOENIX_CART_ROOT/includes/system/autoloader.php" ]]; then
  echo "Missing catalog at PHOENIX_CART_ROOT=$PHOENIX_CART_ROOT" >&2
  exit 1
fi

if ! command -v apache2ctl >/dev/null 2>&1 && ! command -v apachectl >/dev/null 2>&1; then
  echo "Apache is required (e.g. apt install apache2 libapache2-mod-php)." >&2
  exit 1
fi

HTTPS_DIR="$ROOT/.cursor/https"
mkdir -p "$HTTPS_DIR"

CERT="$HTTPS_DIR/phoenix-cart-tests.crt"
KEY="$HTTPS_DIR/phoenix-cart-tests.key"

if [[ ! -f "$CERT" || ! -f "$KEY" ]]; then
  openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
    -keyout "$KEY" -out "$CERT" \
    -subj "/CN=${PHOENIX_HTTPS_HOST}" \
    -addext "subjectAltName=IP:${PHOENIX_HTTPS_HOST}"
fi

if command -v mysql >/dev/null 2>&1; then
  MYSQL=(mysql -h "$PHOENIX_DB_HOST" -P "$PHOENIX_DB_PORT" -u "$PHOENIX_DB_USER" -p"$PHOENIX_DB_PASSWORD" "$PHOENIX_DB_NAME")
  "${MYSQL[@]}" < fixtures/http/enable_ssl_session_check.sql
fi

APACHE_CTL="apache2ctl"
if ! command -v "$APACHE_CTL" >/dev/null 2>&1; then
  APACHE_CTL="apachectl"
fi

APACHE2_DIR="${APACHE2_DIR:-/etc/apache2}"
if [[ ! -d "$APACHE2_DIR/mods-enabled" ]]; then
  echo "Debian-style Apache expected at APACHE2_DIR=$APACHE2_DIR (mods-enabled missing)." >&2
  exit 1
fi

export APACHE_RUN_USER="${APACHE_RUN_USER:-www-data}"
export APACHE_RUN_GROUP="${APACHE_RUN_GROUP:-www-data}"
export APACHE_PID_FILE="${APACHE_PID_FILE:-$HTTPS_DIR/apache2.pid}"
export APACHE_RUN_DIR="${APACHE_RUN_DIR:-$HTTPS_DIR/run}"
export APACHE_LOCK_DIR="${APACHE_LOCK_DIR:-$HTTPS_DIR/lock}"
export APACHE_LOG_DIR="${APACHE_LOG_DIR:-$HTTPS_DIR/logs}"
mkdir -p "$APACHE_RUN_DIR" "$APACHE_LOCK_DIR" "$APACHE_LOG_DIR"

SITE_CONF="$HTTPS_DIR/phoenix-cart-tests-ssl.conf"
cat > "$SITE_CONF" <<EOF
ServerRoot "${APACHE2_DIR}"
User ${APACHE_RUN_USER}
Group ${APACHE_RUN_GROUP}
DefaultRuntimeDir ${APACHE_RUN_DIR}
PidFile ${APACHE_PID_FILE}
Mutex file:${APACHE_LOCK_DIR} default

IncludeOptional ${APACHE2_DIR}/mods-enabled/*.load
IncludeOptional ${APACHE2_DIR}/mods-enabled/*.conf

ErrorLog ${APACHE_LOG_DIR}/error.log
CustomLog ${APACHE_LOG_DIR}/access.log combined
LogLevel warn

Listen ${PHOENIX_HTTPS_PORT}

<VirtualHost *:${PHOENIX_HTTPS_PORT}>
    ServerName ${PHOENIX_HTTPS_HOST}
    DocumentRoot ${PHOENIX_CART_ROOT}
    SSLEngine on
    SSLCertificateFile ${CERT}
    SSLCertificateKeyFile ${KEY}
    SSLOptions +StdEnvVars
    SSLSessionCache none

    <Directory ${PHOENIX_CART_ROOT}>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        SSLOptions +StdEnvVars
    </Directory>

    <FilesMatch \\.php\$>
        SetHandler application/x-httpd-php
    </FilesMatch>
</VirtualHost>
EOF

echo "Serving $PHOENIX_CART_ROOT at https://${PHOENIX_HTTPS_HOST}:${PHOENIX_HTTPS_PORT}/"
exec "$APACHE_CTL" -f "$SITE_CONF" -D FOREGROUND
