# Wave 4 HTTP fixtures

Wave 4 does not add SQL beyond wave 3. This folder documents **catalog configure** for acceptance tests.

## `includes/local/configure.php`

Http tests generate **`$PHOENIX_CART_ROOT/includes/local/configure.php`** at runtime (catalog `.gitignore` / not in this repo). Values come from:

- `PHOENIX_HTTP_BASE_URL` → `HTTP_SERVER`
- `PHOENIX_DB_*` → `DB_SERVER`, credentials, database name

Import MySQL fixtures before starting the shop:

```bash
bash fixtures/import-mysql-fixtures.sh
bash scripts/http-server.sh
```

See [`docs/wave-4-design-brief.md`](../../docs/wave-4-design-brief.md).
