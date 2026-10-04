# Release certification

Release certification checks that **this test repository** passes the harness against a defined **CE-PhoenixCart** git ref, using the same fixture SQL and (optionally) browser snapshots committed here.

## Catalog pin

| Repo | Ref |
|------|-----|
| [CE-PhoenixCart/PhoenixCart](https://github.com/CE-PhoenixCart/PhoenixCart) | [`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt) (default **`master`**, current release branch) |
| `phoenix-cart-tests` | Same ref when publishing a certified test release; override with **`PHOENIX_CATALOG_TAG`** |

[`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt) also documents which CE ref the vendored SQL was copied from. [`vendored_install_sql_test`](../tests/unit/invariants/vendored_install_sql_test.php) checks that copy against the catalog checkout. Refresh [`fixtures/phoenix.sql`](../fixtures/phoenix.sql) and [`fixtures/phoenix_data_sample.sql`](../fixtures/phoenix_data_sample.sql) when upstream install files change (see [`fixtures/README.md`](../fixtures/README.md)).

## What runs

Release certification matches the full harness on the pinned catalog: stack, optional browser, installer, and optional HTTPS. [`scripts/release-certification.sh`](../scripts/release-certification.sh) checks out the catalog at the pin, imports fixtures, starts the HTTP server when enabled, then:

1. **`composer test:stack`** (unit + integration + HTTP)
2. **`composer test:browser`** when **`PHOENIX_BROWSER_ENABLED=1`** and the HTTP server is enabled
3. **`composer test:installer`** (disposable catalog + admin suite; set **`PHOENIX_MYSQL_ROOT_PASSWORD`** on TCP root, same as CI)
4. **`composer test:https`** when **`PHOENIX_HTTPS_ENABLED=1`** (Apache TLS — see [`https-tests.md`](https-tests.md))

GitHub Actions certification uses **PHP 8.4** and **MariaDB 10.11**, installs Apache for HTTPS, and sets **`PHOENIX_MYSQL_ROOT_PASSWORD=root`**. MySQL 8 engine coverage for unit + integration only is the **`mysql8-unit-integration`** job in [`.github/workflows/phpunit-mysql.yml`](../.github/workflows/phpunit-mysql.yml) (see [`TESTING.md`](../TESTING.md)).

Entry point: **`composer release-certify`**. Requires MariaDB/MySQL, PHP, Composer, and the same env vars as local HTTP tests.

This certification path validates the **pinned CE ref + committed fixture SQL + harness suites** on the runner OS. It does **not** include a PHP or MySQL/MariaDB version matrix — use project CI images and local docs for engine choices.

```bash
export PHOENIX_DB_HOST=127.0.0.1
export PHOENIX_DB_USER=phoenix
export PHOENIX_DB_PASSWORD=phoenix
export PHOENIX_DB_NAME=phoenix_test
# Optional: export PHOENIX_CATALOG_TAG=1.1.0.8
composer release-certify
```

Optional Playwright:

```bash
export PHOENIX_BROWSER_ENABLED=1
composer release-certify
```

## Publishing a certified test release

1. Refresh fixtures from the CE ref; update **`fixtures/catalog_pin.txt`** if the pin changes.
2. Run **`composer release-certify`** on Linux so Playwright snapshots and integration match CI.
3. Tag this repo with the same CE release tag (when certifying a semver release) and push the tag.

GitHub Actions [`.github/workflows/release-certification.yml`](../.github/workflows/release-certification.yml) re-runs certification on **tag push** (and manual dispatch).
