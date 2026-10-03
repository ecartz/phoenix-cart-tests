# Testing strategy for CE Phoenix Cart

This repository holds **all** automated tests for [CE-PhoenixCart](https://github.com/CE-PhoenixCart/PhoenixCart). The catalog repo contains **functional code only**; it does not ship PHPUnit, test classes, or fixture data.

CE-PhoenixCart CI runs this suite against the catalog revision under test. This repo stays public so upstream can clone it without secrets.

## How CI selects a test revision

| PhoenixCart context | Test repo git ref |
|---------------------|-------------------|
| **Tagged release** (every CE release is tagged) | Same tag on `phoenix-cart-tests` if that tag exists |
| **Everything else** (PRs, branch pushes, untagged commits) | `main` |

Local and cloud runs set `PHOENIX_CART_ROOT` to the catalog tree (sibling `../PhoenixCart`, `./PhoenixCart`, or env override). See `tests/bootstrap.php`. CI and Cloud clone **`master`** via [`fixtures/catalog_pin.txt`](fixtures/catalog_pin.txt) and [`scripts/clone-catalog.sh`](scripts/clone-catalog.sh) (current CE release branch).

## Documentation

Suite runbooks: [`documents/README.md`](documents/README.md). Deliberate exclusions: [`SKIPPED.md`](SKIPPED.md).

## Configuration constants (unit vs MySQL)

In a running shop, most `MODULE_*`, `TEMPLATE_*`, and related constants are **not** hard-coded. The application segment [`read_configuration.php`](https://github.com/CE-PhoenixCart/PhoenixCart/blob/master/includes/system/segments/application/read_configuration.php) loads rows from the `configuration` table and **`define()`s each key**:

```php
$db->fetch_all('SELECT configuration_key, configuration_value FROM configuration');
```

Module enablement strings (for example `MODULE_CONTENT_INSTALLED`) and template block groups therefore **come from the database**. Payment, shipping, and content modules also consult `configuration` in `abstract_module` constructors.

Implications for testing:

| Approach | When it works | Limitation |
|----------|----------------|------------|
| **`define('KEY', 'value')` in a test** | The code under test reads the constant **after** your define, and nothing re-runs `read_configuration` | Does not prove DB/config integration; wrong if bootstrap order defines keys differently |
| **Stub `$GLOBALS` / fake `hooks` / fake `Template`** | Template content paths, hook callbacks | Does not load real module lists from config |
| **Mock `$GLOBALS['db']` before running shop code** | Code paths that call `$db->fetch_all()` / `$db->query()` with **predictable** SQL; load configuration from in-memory rows then `define()` keys (same effect as `read_configuration`) | Must implement or stub the methods Phoenix actually calls; fragile if production SQL changes; no real mysqli/transaction behavior |
| **Minimal `configuration` (+ related) rows in a fixture DB** | Full SQL, joins, `database_core`, checkout segments | Requires database infrastructure (see [`documents/integration-tests.md`](documents/integration-tests.md)); ground truth for “does the query work?” |

So the harness splits **autoload-only unit** work from **real SQL** integration, with **mock `$GLOBALS['db']`** in between for configuration-driven unit paths.

### Mocking the database

Use [`configuration_test_helper::load_from_configuration_rows()`](tests/support/configuration_test_helper.php) or install [`mock_catalog_database`](tests/support/mock_catalog_database.php) via `install_as_global()`:

1. Build `configuration_key` / `configuration_value` rows (and optional `table_rows` keyed by table name).
2. Helper assigns `$GLOBALS['db']` and `require`s `read_configuration.php` so constants match production order; use `#[RunInSeparateProcess]` when redefining keys.
3. `query()` returns [`mock_catalog_query_result`](tests/support/mock_catalog_query_result.php) with `fetch_assoc()`; `fetch_all()` accepts a string or that result. Matching is by `FROM {table}` substring only (no WHERE filtering).
4. Tag tests `#[Group('mockdb')]`. Run `vendor/bin/phpunit --group mockdb` for the mock subset.

**Covered by the mock today:** configuration load; `build_blocks` / `get_content_modules`; `Country` / `Zone` / `Tax::fetch_classes` / `currencies` / `language` / `Product::fetch_name` / `info_pages`; `cm_header_breadcrumb` Schema paths; `isEnabled()`.

**Still use real MySQL, not mocks, when:**

- SQL is complex (joins, subqueries, dynamic fragments) and correctness of the query matters
- Code uses `mysqli_num_rows`, transactions, or `database_core` edge cases
- Checkout/order/cart segments write to multiple tables
- You want CE-PhoenixCart CI to prove the catalog works against a real engine

Mocked-db tests live under `tests/unit/` with `#[Group('mockdb')]`. MySQL-backed tests live under `tests/integration/` with `#[Group('mysql')]`.

---

## PHPUnit suites (`phpunit.xml`)

| Composer script | Testsuite | Directories |
|-----------------|-----------|-------------|
| **`composer test:all`** | **`all`** | Unit + Integration + Http + Https |
| **`composer test:stack`** | **`stack`** | Unit + Integration + Http (Cloud / release PHPUnit) |
| **`composer test`** | **`unit`** | `tests/unit/` |
| **`composer test:mysql`** | **`integration`** | `tests/integration/` |
| **`composer test:http`** | **`http`** | `tests/http/` |
| **`composer test:https`** | **`https`** | `tests/https/` |

---

## Tooling summary

| Layer | Primary tools | Typical CI needs |
|-------|----------------|------------------|
| Unit | PHPUnit, catalog autoload | PHP, clone catalog + this repo |
| Mock db (`mockdb`) | PHPUnit + mock `$GLOBALS['db']` | Same as unit (no MySQL service) |
| Integration | PHPUnit + MySQL fixtures | DB service, import SQL |
| HTTP | Symfony HttpClient, `php -S` | DB + web server |
| Browser | Playwright | DB + web server + Node + Chromium |
| HTTPS | Apache, curl | DB + Apache TLS |
| Optional | Payment sandbox secrets, release certify | Manual workflows / tag push |

GitHub Actions [`.github/workflows/phpunit-mysql.yml`](.github/workflows/phpunit-mysql.yml) runs stack, browser, and HTTPS via [`scripts/full-stack-test.sh`](scripts/full-stack-test.sh), matching **`composer cloud-test`**.

## Repository layout

| Path | Role |
|------|------|
| `tests/unit/` | Unit tests; mock-db tests use `#[Group('mockdb')]` here |
| `tests/integration/` | `#[Group('mysql')]` tests against fixture SQL |
| `tests/support/` | Shared support (`mock_catalog_database`, `configuration_test_helper`, `phoenix_test_case`, …) |
| `tests/http/` | HTTP acceptance (`#[Group('http')]`) |
| `tests/https/` | Apache HTTPS (`composer test:https` only) |
| `tests/browser/` | Playwright specs |
| `playwright.config.ts` | Playwright base URL + Chromium project |
| `fixtures/` | SQL seeds; [`fixtures/http/README.md`](fixtures/http/README.md) for HTTP-related SQL and configure |
| `documents/` | Suite documentation ([`documents/README.md`](documents/README.md)) |
| `SKIPPED.md` | Explicit deferrals (not yet covered) |

## Agent and human conventions

- Optional local [`AGENTS.md`](AGENTS.md) (gitignored) mirrors agent-oriented notes; the bullets below are the tracked source of truth for contributors and CI.
- Namespaces: `PhoenixCart\Tests\unit\<area>\...` or `PhoenixCart\Tests\integration\...` (path segments lowercase); support classes use catalog-style **snake_case** filenames and class names.
- **Indentation:** use **4 spaces** per level in `tests/`, `scripts/`, and root PHP. Do not mix 2-space blocks into test code.
- **PHP class layout (named types):** put the opening `{` on the same line as the `class` / `trait` / `interface` / `enum` declaration; leave one blank line after `{` before members and one blank line before the closing `}`. Examples: [`configuration_test_helper.php`](tests/support/configuration_test_helper.php), [`tickable_test.php`](tests/unit/html/tickable_test.php).
- **PHP function / method braces:** put `{` on the last line of the signature—after the return type when present (e.g. `): void {`) or after `)` when there is no return type (e.g. `function __construct() {`). Multi-line parameter lists keep their line breaks; only move the brace.
- **Inline anonymous classes:** `new class { … }` stubs inside a test method are exempt from the named-class blank-line rules; keep them compact on purpose (see [`html_test_case.php`](tests/unit/html/html_test_case.php)).
- **Catalog fixture PHP:** files under [`fixtures/http/`](fixtures/http/) are copied into the shop at runtime and follow **Phoenix 2-space** layout, not test layout.
- Prefer **behavior** assertions and data providers.
- Do not copy Phoenix implementation into this repo.
- When skipping, update `SKIPPED.md` with **reason** and which suite would cover it later.
- For configuration-dependent behavior, prefer **mock db + `read_configuration`** or **fixture MySQL** over long manual `define()` lists.

## Running locally

```bash
composer install
export PHOENIX_CART_ROOT=/path/to/PhoenixCart   # optional
vendor/bin/phpunit
```

Clone upstream catalog if needed:

```bash
git clone --depth 1 --branch master https://github.com/CE-PhoenixCart/PhoenixCart.git PhoenixCart
# or: bash scripts/clone-catalog.sh
```

Windows developers may see path-separator or PHP version differences versus Linux CI; treat Linux CI on CE-PhoenixCart as the merge gate once workflows exist.
