# Integration tests

Integration tests use a **real** MySQL or MariaDB database loaded from vendored fixture SQL in this repo. They prove catalog classes and SQL against the same schema and seed data CE Phoenix Cart ships in `install/phoenix.sql` and `install/phoenix_data_sample.sql`.

## Location and command

| Item | Value |
|------|--------|
| Directory | [`tests/integration/`](../tests/integration/) |
| Group | **`#[Group('mysql')]`** |
| PHPUnit testsuite | `integration` |
| Run | **`composer test:mysql`** when MySQL is available |

## Enable

```bash
bash fixtures/import-mysql-fixtures.sh
export PHOENIX_MYSQL_ENABLED=1
export PHOENIX_DB_HOST=127.0.0.1
export PHOENIX_DB_NAME=phoenix_test
export PHOENIX_DB_USER=phoenix
export PHOENIX_DB_PASSWORD=phoenix
export PHOENIX_CART_ROOT=/path/to/PhoenixCart
composer test:mysql
```

Docker Compose is defined in [`docker-compose.mysql.yml`](../docker-compose.mysql.yml). CI uses a MariaDB service container (see [`.github/workflows/phpunit-mysql.yml`](../.github/workflows/phpunit-mysql.yml)).

## Harness

[`tests/support/mysql_bootstrap.php`](../tests/support/mysql_bootstrap.php) defines `DB_*` and catalog constants from the environment. [`mysql_test_case`](../tests/support/mysql_test_case.php) connects `Database`, assigns `$GLOBALS['db']`, and loads `read_configuration.php` (T1 bootstrap) without full `application_top.php`.

Session handling stays minimal in integration tests (plain PHP session from bootstrap; no full `start_session` segment unless a test requires it).

## Fixtures

Import order and refresh procedure are documented in [`fixtures/README.md`](../fixtures/README.md). The catalog ref used for clones is recorded in [`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt).

Examples of what integration covers: `Tax::fetch`, `abstract_module::check()` / install/remove, `database_core::perform`, `info_pages` helpers, `Product::fetch_name`, and content modules such as `cm_header_breadcrumb` with sample product data.
