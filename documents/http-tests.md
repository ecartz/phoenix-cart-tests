# HTTP acceptance tests

HTTP tests send real requests to a **running** CE Phoenix Cart shop backed by the fixture database. PHPUnit uses **Symfony HttpClient** with a cookie jar so sessions behave like a browser for server-rendered flows.

## Location and command

| Item | Value |
|------|--------|
| Directory | [`tests/http/`](../tests/http/) |
| Group | **`#[Group('http')]`** |
| PHPUnit testsuite | `http` |
| Run | **`composer test:http`** when the shop is up |

HTTP tests are included in **`composer test:stack`** and **`composer test:all`** when env flags are set; they are **not** in the default **`composer test`** (unit only).

## Prerequisites

1. Import fixtures: **`bash fixtures/import-mysql-fixtures.sh`**
2. Write catalog configure and start the built-in server: **`bash scripts/http-server.sh`** (document root = **`PHOENIX_CART_ROOT`**, default port **8765**)
3. Set skip gate and origin:

```bash
export PHOENIX_HTTP_ENABLED=1
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
export PHOENIX_MYSQL_ENABLED=1
export PHOENIX_CART_ROOT=./PhoenixCart
composer test:http
```

[`tests/support/http_bootstrap.php`](../tests/support/http_bootstrap.php) and [`http_test_case`](../tests/support/http_test_case.php) generate **`includes/local/configure.php`** under the catalog from `PHOENIX_HTTP_BASE_URL` and `PHOENIX_DB_*`. That file is not committed (see [`fixtures/http/README.md`](../fixtures/http/README.md)).

Unset **`PHOENIX_HTTP_ENABLED`** and the Http suite skips (same pattern as **`PHOENIX_MYSQL_ENABLED`** for integration).

## Coverage

Examples in this suite:

- Homepage and category/product GET (`index_smoke_test`, `product_info_test`, `category_listing_test`)
- Cart session via `buy_now` and `shopping_cart.php` (`shopping_cart_test`)
- Logged-in checkout through COD and flat shipping to `checkout_success.php` (`checkout_cod_test`; fixture customer in [`fixtures/http/seed_customer.sql`](../fixtures/http/seed_customer.sql))
- Info and slug pages (`info_page_test`); extra SQL in [`fixtures/http/`](../fixtures/http/)
- Redirect hardening for `Href::redirect` entrypoints (`href_redirect_test`)
- Request user-agent / IP mismatch → login redirect (`request_security_test`; uses `enable_session_security_checks.sql`)
- Timed homepage smoke (`homepage_timing_test`; budget via **`PHOENIX_HTTP_BUDGET_SECONDS`**, default 10)

Payment sandbox config smoke lives in the same directory but runs via **`composer test:payment-sandbox`** ([`payment-sandbox.md`](payment-sandbox.md)).

SSL session id checks run over Apache HTTPS, not plain HTTP — see [`https-tests.md`](https-tests.md).
