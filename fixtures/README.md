# Wave 3 MySQL fixtures

## `phoenix.sql`

Vendored copy of CE-PhoenixCart `install/phoenix.sql` for reproducible Integration tests.

| Field | Value |
|-------|--------|
| **Source** | `PhoenixCart/install/phoenix.sql` (sibling catalog checkout) |
| **Pin** | Refresh when CE ships a tagged release; note the tag in the commit message |
| **Charset** | `utf8mb4` / `utf8mb4_unicode_ci` on all tables |
| **Engine** | MariaDB 10.11+ or MySQL 8.0+ |

## Refresh

```bash
cp "$PHOENIX_CART_ROOT/install/phoenix.sql" fixtures/phoenix.sql
```

Commit with message: `Fixture from CE tag <tag>`.

## Import (local or CI)

```bash
mysql -h "$PHOENIX_DB_HOST" -P "${PHOENIX_DB_PORT:-3306}" \
  -u "$PHOENIX_DB_USER" -p"$PHOENIX_DB_PASSWORD" \
  "$PHOENIX_DB_NAME" < fixtures/phoenix.sql
```

## Supplement (PR2+)

`wave3-supplement.sql` will hold demo products and other rows not present in the upstream install dump.
