# Wave 4 HTTP fixtures

Wave 4 adds **`publish_info_pages.sql`** (applied by `fixtures/import-mysql-fixtures.sh` after the wave 3 seeds). CE install leaves footer info pages as draft (`pages_status=0`); `info.php` only renders **published** pages (`pages_status=1`).

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

`composer cloud-test` drops and re-imports `phoenix_test` on each run so warm Cloud VMs stay aligned with the committed seeds.

See [`docs/wave-4-design-brief.md`](../../docs/wave-4-design-brief.md).
