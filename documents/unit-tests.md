# Unit tests

Unit tests exercise **real** Phoenix Cart classes through the catalog autoloader (`tests/bootstrap.php` sets `DIR_FS_CATALOG` and loads `catalog_autoloader`). They do **not** boot `application_top.php` or require a running database process.

## Location and command

| Item | Value |
|------|--------|
| Directory | [`tests/unit/`](../tests/unit/) |
| PHPUnit testsuite | `unit` |
| Default run | **`composer test`** |

Nested folders group tests by area (`html/`, `support/`, `content/`, `template/`, `configuration/`, `invariants/`). Class namespaces use lowercase segments that match those paths (see [`AGENTS.md`](../AGENTS.md)).

## What belongs here

Typical targets:

- Versioned helpers (`Text`, `Password`, `Path`, `Href`, `Form`, date transformers, …)
- HTML builders and small catalog classes that only need autoload
- Content modules that buffer a `tpl_` file with stub `$GLOBALS['Template']` / `Linker` / `messageStack` and hand `define()` for isolated module keys
- Template mapping tests with stubbed `Template` / hooks when no live module list is required

Most shop behavior is configuration- or session-driven; many classes are intentionally covered in integration, HTTP, or browser suites instead. See [`SKIPPED.md`](../SKIPPED.md).

## Mock database (`#[Group('mockdb')]`)

Some unit tests install a mock [`$GLOBALS['db']`](../tests/support/mock_catalog_database.php) via [`configuration_test_helper`](../tests/support/configuration_test_helper.php) so `read_configuration.php` can `define()` keys from fake query results. That pattern avoids MySQL while still loading constants the way production does after SQL import.

Use real MySQL ([`integration-tests.md`](integration-tests.md)) when the test’s point is SQL correctness, persistence, or segments that write to the database.

## Catalog root

Set **`PHOENIX_CART_ROOT`** to a checkout of [CE-PhoenixCart/PhoenixCart](https://github.com/CE-PhoenixCart/PhoenixCart), or clone with [`scripts/clone-catalog.sh`](../scripts/clone-catalog.sh) (ref from [`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt)).
