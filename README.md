# CE Phoenix Cart tests

PHPUnit harness for [CE Phoenix Cart](https://github.com/CE-PhoenixCart/PhoenixCart). Tests live here so the shop fork stays close to upstream.

## Requirements

- PHP 7.3+ with `mbstring` and `xml` (Integration also needs **`mysqli`**; HTTP suite needs **PHP 8.2+** for Symfony HttpClient)
- Composer
- Phoenix Cart catalog tree (sibling `../PhoenixCart` or shallow clone in `./PhoenixCart`)

## Run locally

```bash
composer install
composer test          # unit suite only (no database)
composer test:stack    # unit + integration + http (Cloud / release PHPUnit phase)
composer test:all      # every PHPUnit class including tests/https (opt-in tests may skip)
composer test:https    # Apache SSL session id only
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

See [`fixtures/README.md`](fixtures/README.md) and [`documents/wave-3-design-brief.md`](documents/wave-3-design-brief.md).

### HTTP (wave 4)

```bash
bash fixtures/import-mysql-fixtures.sh
bash scripts/http-server.sh   # separate terminal
export PHOENIX_HTTP_ENABLED=1
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
export PHOENIX_DB_HOST=127.0.0.1
export PHOENIX_DB_NAME=phoenix_test
export PHOENIX_DB_USER=phoenix
export PHOENIX_DB_PASSWORD=phoenix
composer test:http
```

See [`documents/wave-4-design-brief.md`](documents/wave-4-design-brief.md).

### Browser (wave 5)

```bash
bash fixtures/import-mysql-fixtures.sh
bash scripts/http-server.sh
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
npm ci
npx playwright install chromium
composer test:browser
```

See [`documents/wave-5-design-brief.md`](documents/wave-5-design-brief.md).

### Release hardening (wave 6)

Timed homepage check runs with **`composer test:http`**. Carousel screenshots with **`composer test:browser`**. Payment sandbox with **`composer test:payment-sandbox`** (secrets). Release certification with **`composer release-certify`**. See [`documents/wave-6-design-brief.md`](documents/wave-6-design-brief.md).

## Layout

| Path | Purpose |
|------|---------|
| `tests/bootstrap.php` | Locates catalog root, registers `catalog_autoloader` |
| `tests/support/phoenix_test_case.php` | Base test case |
| `tests/support/mysql_test_case.php` | Integration base (real `Database`, T1 bootstrap) |
| `tests/support/mock_catalog_database.php` | In-memory `$db` double for wave 2b |
| `tests/unit/` | Unit and mock-db tests |
| `tests/integration/` | `@group mysql` tests against fixture SQL |
| `tests/http/` | `@group http` acceptance tests (running shop + DB) |
| `tests/browser/` | Playwright specs (wave 5) |
| `playwright.config.ts` | Playwright config |
| `scripts/http-server.sh` | PHP built-in server for wave 4 |
| `fixtures/phoenix.sql` | Vendored CE install schema + seed |
| `fixtures/phoenix_data_sample.sql` | Vendored CE sample catalog (import after `phoenix.sql`) |

## License

GPL-2.0-or-later, same as Phoenix Cart. See [`LICENSE`](LICENSE).
