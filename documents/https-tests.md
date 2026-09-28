# HTTPS tests

HTTPS tests exercise **`Request::check_ssl_session_id()`** over real TLS where PHP receives **`HTTPS=on`** and **`SSL_SESSION_ID`** from the web server. The built-in PHP server used for HTTP tests does not provide those variables.

## Location and command

| Item | Value |
|------|--------|
| Directory | [`tests/https/`](../tests/https/) |
| Group | **`#[Group('https')]`** |
| PHPUnit testsuite | `https` |
| Run | **`composer test:https`** |

**`composer test:stack`** runs unit + integration + HTTP only. **`composer test:all`** includes **`tests/https/`**; cases skip when HTTPS is not configured.

## Server

[`scripts/https-server.sh`](../scripts/https-server.sh) (Linux / CI):

- Self-signed certificate under **`.cursor/https/`** (gitignored)
- Document root **`PHOENIX_CART_ROOT`**, default port **8443** (`PHOENIX_HTTPS_PORT`)
- Apache **`SSLOptions +StdEnvVars`** and **`SSLSessionCache none`** so each new connection gets a fresh TLS session id

Does not replace [`scripts/http-server.sh`](../scripts/http-server.sh). Cloud and CI run HTTP on **8765**, then Apache on **8443** for this suite.

## Test behavior

[`ssl_session_id_test.php`](../tests/https/ssl_session_id_test.php):

- Skips unless **`PHOENIX_HTTPS_ENABLED=1`**
- Requires **`PHOENIX_HTTP_ENABLED=1`** so configure is written (point **`PHOENIX_HTTP_BASE_URL`** at the HTTPS origin)
- Applies [`fixtures/http/enable_ssl_session_check.sql`](../fixtures/http/enable_ssl_session_check.sql) (default install keeps **`SESSION_CHECK_SSL_SESSION_ID`** false)
- Two **`curl`** invocations with a shared cookie jar; asserts **302** to **`ssl_check.php`** on the second request

## Run locally (Linux)

```bash
bash fixtures/import-mysql-fixtures.sh
bash scripts/https-server.sh
export PHOENIX_HTTPS_ENABLED=1
export PHOENIX_HTTP_ENABLED=1
export PHOENIX_HTTP_BASE_URL=https://127.0.0.1:8443
export PHOENIX_HTTPS_BASE_URL=https://127.0.0.1:8443
composer test:https
```

Install Apache and the PHP module if needed (`apache2`, `libapache2-mod-php`). Warm VMs may need **`/run/lock/apache2`** created before Apache starts (handled in `https-server.sh` and cloud install).
