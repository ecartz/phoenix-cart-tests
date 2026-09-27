# Wave 4 HTTP fixtures

Wave 4 adds HTTP fixture SQL (applied by [`fixtures/import-mysql-fixtures.sh`](../import-mysql-fixtures.sh) after the wave 3 seeds):

- **`publish_info_pages.sql`** — publish footer slugs so `info.php` serves them (`pages_status=1`).
- **`enable_session_security_checks.sql`** — turn on `SESSION_CHECK_USER_AGENT` and `SESSION_CHECK_IP_ADDRESS` for Request mismatch HTTP tests (install defaults are `False`).
- **`enable_ssl_session_check.sql`** — turn on `SESSION_CHECK_SSL_SESSION_ID` for wave 7 HTTPS tests only (not applied by default import; see [`scripts/https-server.sh`](../../scripts/https-server.sh) or test bootstrap).

Wave 6 part 3: when **`PHOENIX_PAYMENT_SANDBOX_ENABLED=1`**, fixture import also runs **`php scripts/apply-payment-sandbox-config.php`** (Stripe test keys from env). See [`documents/payment-sandbox-ci.md`](../../documents/payment-sandbox-ci.md).

This folder also documents **catalog configure** for acceptance tests.

## `includes/local/configure.php`

Http tests generate **`$PHOENIX_CART_ROOT/includes/local/configure.php`** at runtime (catalog `.gitignore` / not in this repo). Values come from:

- `PHOENIX_HTTP_BASE_URL` → `HTTP_SERVER`
- `PHOENIX_DB_*` → `DB_SERVER`, credentials, database name

Import MySQL fixtures before starting the shop:

```bash
bash fixtures/import-mysql-fixtures.sh
bash scripts/http-server.sh
```

`composer cloud-test` drops and re-imports `phoenix_test` on each run so warm Cloud VMs stay aligned with the committed seeds. It also applies default `PHOENIX_HTTP_*` values when an older `.cursor/cloud.env` omits them. When **`PHOENIX_BROWSER_ENABLED=1`** (default after cloud install), it runs **`composer test:browser`** after PHPUnit.

See [`documents/wave-4-design-brief.md`](../../documents/wave-4-design-brief.md), [`documents/wave-5-design-brief.md`](../../documents/wave-5-design-brief.md), and [`documents/wave-7-design-brief.md`](../../documents/wave-7-design-brief.md).
