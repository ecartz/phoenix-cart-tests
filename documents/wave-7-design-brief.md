# Wave 7 design brief

**SSL session id** — exercise `Request::check_ssl_session_id()` over real HTTPS where PHP receives `SSL_SESSION_ID` from the web server.

**Depends on:** wave 3 MySQL fixtures, wave 4 `http_bootstrap` / `includes/local/configure.php`, Apache with `mod_ssl` + PHP (not `php -S`).

---

## Why Apache

`Application::check_ssl_session_id()` runs only when `getenv('HTTPS') === 'on'` and a PHP session exists. It stores `getenv('SSL_SESSION_ID')` in `$_SESSION` and, on a later request with a different TLS session id, destroys the session and redirects to **`ssl_check.php`**.

The built-in PHP server never sets `HTTPS` or `SSL_SESSION_ID`. Wave 4 HTTP and wave 5 Playwright stay on **`scripts/http-server.sh`** (`php -S`).

Apache must expose the TLS session id to PHP:

- **`SSLOptions +StdEnvVars`**
- **`SSLSessionCache none`** so each new TCP connection gets a fresh id (session resume would reuse the same `SSL_SESSION_ID`).

---

## Server

[`scripts/https-server.sh`](../scripts/https-server.sh) (Linux / Cloud opt-in):

- Self-signed cert under **`.cursor/https/`** (gitignored).
- Document root **`PHOENIX_CART_ROOT`**, port **8443** (override with **`PHOENIX_HTTPS_PORT`**).
- Applies [`fixtures/http/enable_ssl_session_check.sql`](../fixtures/http/enable_ssl_session_check.sql) when `mysql` is available (sets **`SESSION_CHECK_SSL_SESSION_ID`** to `True`; install default is `False`).

Does **not** replace [`scripts/http-server.sh`](../scripts/http-server.sh). Opt-in locally with **`composer test:https`**. **`composer cloud-test`** starts Apache on **8443** and runs **`composer test:https`** after the plain-HTTP and browser steps when **`PHOENIX_HTTPS_ENABLED=1`** in **`.cursor/cloud.env`** (PHPUnit **`test:all`** on **8765** keeps **`PHOENIX_HTTPS_ENABLED` unset** so this test skips until the HTTPS phase).

---

## Test

[`tests/Http/ssl_session_id_test.php`](../tests/Http/ssl_session_id_test.php):

- Groups **`http`** and **`https`**.
- Skips unless **`PHOENIX_HTTPS_ENABLED=1`** (before probing the shop).
- Requires **`PHOENIX_HTTP_ENABLED=1`** so configure is written (set **`PHOENIX_HTTP_BASE_URL`** to the same HTTPS origin as **`PHOENIX_HTTPS_BASE_URL`**).
- Two separate **`curl`** processes, shared cookie jar, **`-k`**, against **`PHOENIX_HTTPS_BASE_URL`** (default `https://127.0.0.1:8443`).
- Asserts the second response is **302** with **`Location`** containing **`ssl_check.php`**.

**`composer test:https`** → `phpunit --group https`.

**`composer test:http`** / **`composer test:all`** still load the class; it skips when the HTTPS flag is unset.

---

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

Install Apache + PHP module if needed, e.g. `apt install apache2 libapache2-mod-php`.
