# HTTP fixture SQL and configure

Additional SQL applied by [`fixtures/import-mysql-fixtures.sh`](import-mysql-fixtures.sh) after the base install and sample catalog:

- **`publish_info_pages.sql`** — publish footer slugs so `info.php` serves them (`pages_status=1`).
- **`enable_session_security_checks.sql`** — turn on `SESSION_CHECK_USER_AGENT` and `SESSION_CHECK_IP_ADDRESS` for Request mismatch HTTP tests (install defaults are `False`).
- **`seed_customer.sql`** — one fixture customer (`phoenix-http-fixture@example.com` / password `phoenix-test`) for HTTP login, `account.php`, and `gdpr.php` tests.
- **`http_local_redirect.php`**, **`http_local_redirect.lang.php`**, and **`http_local_redirect_return.php`** — harness payment module and `ext/modules/payment/http_local_redirect/return.php` copied into the catalog only during [`checkout_local_redirect_test.php`](../../tests/http/checkout_local_redirect_test.php) (see [`http_local_redirect_bootstrap.php`](../../tests/support/http_local_redirect_bootstrap.php)).
- **`http_storefront_hook_marker.php`** — harness `shop/siteWide` hook copied into the catalog during [`storefront_hook_fixture_test.php`](../../tests/installer/storefront_hook_fixture_test.php) (see [`installer_storefront_hook_bootstrap.php`](../../tests/support/installer_storefront_hook_bootstrap.php)); toggled via **`HTTP_TEST_STOREFRONT_HOOK_MARKER_STATUS`**.
- Checkout edge HTTP tests may insert temporary catalog rows (virtual download attribute on product 3, geo zone for COD zone) via [`http_checkout_fixture_sql.php`](../../tests/support/http_checkout_fixture_sql.php); [`http_orders_lookup.php`](../../tests/support/http_orders_lookup.php) reads `orders.payment_method` for assertions.
- **`enable_ssl_session_check.sql`** — turn on `SESSION_CHECK_SSL_SESSION_ID` for HTTPS tests only (not applied by default import; see [`scripts/https-server.sh`](../../scripts/https-server.sh) or test bootstrap).

When **`PHOENIX_PAYMENT_SANDBOX_ENABLED=1`**, fixture import also runs **`php scripts/apply-payment-sandbox-config.php`** (Stripe test keys from env). See [`documents/payment-sandbox.md`](../../documents/payment-sandbox.md).

## `includes/local/configure.php`

Http tests generate **`$PHOENIX_CART_ROOT/includes/local/configure.php`** at runtime (catalog `.gitignore` / not in this repo). Values come from:

- `PHOENIX_HTTP_BASE_URL` → `HTTP_SERVER`
- `PHOENIX_DB_*` → `DB_SERVER`, credentials, database name

PHPUnit Http tests and [`scripts/write-http-local-configure.php`](../../scripts/write-http-local-configure.php) write this file when **`PHOENIX_HTTP_ENABLED=1`**. Cloud test and release certification call the script before Playwright so the shop serves the catalog instead of the install welcome page.

[`cookie_jar_http_client`](../../tests/support/cookie_jar_http_client.php) follows redirects itself (inner HttpClient uses `max_redirects: 0`) so `Set-Cookie` on login POST responses—especially after `SESSION_RECREATE`—is kept before the redirect target is fetched.

Import MySQL fixtures before starting the shop:

```bash
bash fixtures/import-mysql-fixtures.sh
bash scripts/http-server.sh
```

`composer cloud-test` drops and re-imports `phoenix_test` on each run so warm Cloud VMs stay aligned with the committed seeds. It also applies default `PHOENIX_HTTP_*` values when an older `.cursor/cloud.env` omits them. When **`PHOENIX_BROWSER_ENABLED=1`** (default after cloud install), it runs **`composer test:browser`** after PHPUnit.

See [`documents/http-tests.md`](../../documents/http-tests.md), [`documents/browser-tests.md`](../../documents/browser-tests.md), and [`documents/https-tests.md`](../../documents/https-tests.md).
