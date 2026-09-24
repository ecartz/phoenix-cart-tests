# Wave 3 MySQL fixtures

## `phoenix.sql`

Vendored copy of CE-PhoenixCart `install/phoenix.sql` for reproducible Integration tests.

| Field | Value |
|-------|--------|
| **Source** | `PhoenixCart/install/phoenix.sql` (sibling catalog checkout) |
| **Pin** | Refresh when CE ships a tagged release; note the tag in the commit message |
| **Charset** | `utf8mb4` / `utf8mb4_unicode_ci` on all tables |
| **Engine** | MariaDB 10.11+ or MySQL 8.0+ |

This is the **base install** dump (schema + configuration/hooks/geo/tax/info pages). It does **not** include sample catalog products.

## `phoenix_data_sample.sql` (part 2+)

Vendored copy of CE-PhoenixCart `install/phoenix_data_sample.sql` — the same optional **“Import Sample Data”** file the web installer loads after `phoenix.sql`.

Use it whenever Integration tests need real product/category rows (`Product::fetch_name`, breadcrumb product paths, listings). Do not hand-roll a parallel “supplement” seed unless CE removes or breaks the upstream sample file.

| Field | Value |
|-------|--------|
| **Source** | `PhoenixCart/install/phoenix_data_sample.sql` |
| **Import order** | `phoenix.sql` first, then `phoenix_data_sample.sql` on the same database |

## Refresh

```bash
cp "$PHOENIX_CART_ROOT/install/phoenix.sql" fixtures/phoenix.sql
cp "$PHOENIX_CART_ROOT/install/phoenix_data_sample.sql" fixtures/phoenix_data_sample.sql
```

Commit with message: `Fixture from CE tag <tag>`.

## Import (local or CI)

Base install (part 1 — tax, modules, smoke):

```bash
mysql -h "$PHOENIX_DB_HOST" -P "${PHOENIX_DB_PORT:-3306}" \
  -u "$PHOENIX_DB_USER" -p"$PHOENIX_DB_PASSWORD" \
  "$PHOENIX_DB_NAME" < fixtures/phoenix.sql
```

With sample catalog (part 2+ — products/listings):

```bash
mysql -h "$PHOENIX_DB_HOST" -P "${PHOENIX_DB_PORT:-3306}" \
  -u "$PHOENIX_DB_USER" -p"$PHOENIX_DB_PASSWORD" \
  "$PHOENIX_DB_NAME" < fixtures/phoenix_data_sample.sql
```
