# Wave 4 HTTP fixtures

Wave 4 adds HTTP fixture SQL (applied by [`fixtures/import-mysql-fixtures.sh`](../import-mysql-fixtures.sh) after the wave 3 seeds):

- **`publish_info_pages.sql`** — publish footer slugs so `info.php` serves them (`pages_status=1`).
- **`enable_session_security_checks.sql`** — turn on `SESSION_CHECK_USER_AGENT` and `SESSION_CHECK_IP_ADDRESS` for Request mismatch HTTP tests (install defaults are `False`).

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

`composer cloud-test` drops and re-imports `phoenix_test` on each run so warm Cloud VMs stay aligned with the committed seeds. It also applies default `PHOENIX_HTTP_*` values when an older `.cursor/cloud.env` omits them.

See [`documents/wave-4-design-brief.md`](../../documents/wave-4-design-brief.md).
