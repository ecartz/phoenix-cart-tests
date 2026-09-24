# CE Phoenix Cart tests

PHPUnit harness for [CE Phoenix Cart](https://github.com/CE-PhoenixCart/PhoenixCart). Tests live here so the shop fork stays close to upstream.

## Requirements

- PHP 7.3+ with `mbstring` and `xml` (Integration also needs **`mysqli`**)
- Composer
- Phoenix Cart catalog tree (sibling `../PhoenixCart` or shallow clone in `./PhoenixCart`)

## Run locally

```bash
composer install
composer test          # Unit suite only (no database)
composer test:all      # Unit + Integration
```

Override catalog location:

```bash
export PHOENIX_CART_ROOT=/path/to/PhoenixCart
composer test
```

### Integration (wave 3 / MySQL)

```bash
docker compose -f docker-compose.mysql.yml up -d
# wait for healthy MariaDB, then:
bash fixtures/import-mysql-fixtures.sh
export PHOENIX_MYSQL_ENABLED=1
export PHOENIX_DB_HOST=127.0.0.1
export PHOENIX_DB_NAME=phoenix_test
export PHOENIX_DB_USER=phoenix
export PHOENIX_DB_PASSWORD=phoenix
composer test:mysql
```

See [`fixtures/README.md`](fixtures/README.md) and [`docs/wave-3-design-brief.md`](docs/wave-3-design-brief.md).

## Layout

| Path | Purpose |
|------|---------|
| `tests/bootstrap.php` | Locates catalog root, registers `catalog_autoloader` |
| `tests/Support/phoenix_test_case.php` | Base test case |
| `tests/Support/mysql_test_case.php` | Integration base (real `Database`, T1 bootstrap) |
| `tests/Support/mock_catalog_database.php` | In-memory `$db` double for wave 2b |
| `tests/Unit/` | Unit and mock-db tests |
| `tests/Integration/` | `@group mysql` tests against fixture SQL |
| `fixtures/phoenix.sql` | Vendored CE install schema + seed |
| `fixtures/phoenix_data_sample.sql` | Vendored CE sample catalog (import after `phoenix.sql`) |

## License

GPL-2.0-or-later, same as Phoenix Cart.
