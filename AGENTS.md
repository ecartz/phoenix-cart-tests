# Agent instructions

PHPUnit tests for CE Phoenix Cart. Exercise **real** shop classes via catalog autoload (`DIR_FS_CATALOG` + `catalog_autoloader` from `tests/bootstrap.php`). Do not reimplement Phoenix code in this repo.

## Conventions

- GPL-2.0-or-later, `declare(strict_types=1)`
- Namespace `PhoenixCart\Tests\unit\<area>\...` or `PhoenixCart\Tests\integration\...` (segments match lowercase directories); extend `phoenix_test_case` or `mysql_test_case`
- Catalog-style **snake_case** for classes under `tests/support/`, `tests/unit/` (e.g. `mock_catalog_database`, `tickable_test`, `html_test_case`, `phoenix_test_case`); one class per file, filename matches class name
- Test methods, data providers, and other members in this repo use **snake_case** as well (e.g. `test_real_link`, `real_link_provider`); PHPUnit `#[DataProvider('real_link_provider')]` attributes
- **`tests/browser/`** Playwright specs: **`const` / `let` bindings** use **snake_case** (Playwright fixture names such as `page` stay as-is); enforced by `tests/unit/invariants/browser_spec_snake_case_test.php`
- Prefer data providers; assert behavior, not source text
- **`tests/unit/html/`** test files match `tickable_test.php` layout: opening brace on the same line as the class/method signature; cast `Stringable` values with `"$object"` in assertions
- If a constructor needs config constants, see [`TESTING.md`](TESTING.md): production loads them from the `configuration` table; tests may use **`define()` only for isolated keys**, a **mock `$GLOBALS['db']`** via `configuration_test_helper` / `mock_catalog_database` (`#[Group('mockdb')]`), or MySQL fixtures — not full `application_top.php` in isolated unit tests
- Run `vendor/bin/phpunit` after each new test class; fix failures before committing
- New shell scripts under `scripts/`, `.cursor/scripts/`, or `fixtures/` must be committed executable (`git add --chmod=+x`; separately from non-executable files); CI runs `scripts/verify-shell-script-modes.sh`
- Do not modify files under `PhoenixCart/` (cloned catalog is read-only for agents)

## Phoenix architecture (for test targeting)

- **Versioned classes** — `includes/system/versioned/` (latest file wins via `class_index`): `Text`, `Guarantor`, `Href`, `Form`, `Password`, `Path`, `File`, date transformers, etc.
- **Catalog classes** — `includes/classes/`, `admin/includes/classes/`
- **Segments** — procedural snippets in `includes/system/segments/` (application, checkout, listing); reached via `application_surface` / `checkout_surface` magic classes
- **HTML / templates** — content modules `includes/modules/content/*/cm_*.php` render via `Template::map()` → `default_template::_get_template_mapping_for()` into `templates/default/` or `tpl_cm_*.php`

There is no `includes/functions/` tree in current Phoenix; legacy procedural helpers are versioned classes or segments.

## Night-one scope

**In scope:** pure unit tests for classes that do not need a live database or full `application_top.php`.

**Out of scope (defer and list in [`SKIPPED.md`](SKIPPED.md) until covered):**

- Real MySQL Integration tests live under `tests/integration/` with `#[Group('mysql')]` and `PHOENIX_MYSQL_ENABLED=1`; mock `$GLOBALS['db']` remains under `#[Group('mockdb')]` in unit tests
- Checkout/application segments that assume a running shop session
- Installer, admin HTTP, payment modules
- Booting `application_top.php`

Content-module `execute()` that only buffers a `tpl_` file is allowed with stub `$GLOBALS['Template']` / product arrays. Mock-db content paths (for example `cm_header_breadcrumb`) use `configuration_test_helper`.

## Parallel agent areas

| Area | Directory | Example targets |
|------|-----------|-----------------|
| Html | `tests/unit/html/` | `Href`, `html_element`, `Form`, `Input`, `Select`, `Image`, `Button` |
| Support | `tests/unit/support/` | `Date`, transformers, `Password`, `Path`, `File`, `cc_validation`, `url_query` |
| Template | `tests/unit/template/` | `default_template` mapping, `Template` block/content helpers without `MODULE_*` DB constants |

## Cursor Cloud specific instructions

Environment install clones `https://github.com/CE-PhoenixCart/PhoenixCart.git` into `./PhoenixCart` when missing, then runs `composer install`.

`tests/bootstrap.php` registers normalized autoload aliases for camelCase versioned classes (e.g. `Text` → `text`) so PHPUnit can load them without full `application_top.php`.

Verify with:

```bash
composer install
[ -d PhoenixCart/includes/system/autoloader.php ] || bash scripts/clone-catalog.sh
composer test
```

Integration (MySQL): `bash fixtures/import-mysql-fixtures.sh`, set `PHOENIX_MYSQL_ENABLED=1` and `PHOENIX_DB_*`, then `composer test:mysql`. Cloud: `composer cloud-test` runs **`composer test:stack`**, optional **`composer test:browser`**, then **`composer test:https`** when **`PHOENIX_HTTPS_ENABLED=1`** in **`.cursor/cloud.env`**. After changing [`.cursor/Dockerfile`](.cursor/Dockerfile), bump `PHOENIX_CLOUD_ENV_REVISION` and start a fresh Cloud environment so the image includes Apache; [`cloud-install.sh`](.cursor/scripts/cloud-install.sh) can still `apt install` Apache on warm VMs until rebuild. See [`documents/integration-tests.md`](documents/integration-tests.md).

HTTP: after fixtures + `bash scripts/http-server.sh`, set `PHOENIX_HTTP_ENABLED=1` and `PHOENIX_HTTP_BASE_URL`, then `composer test:http`. See [`documents/http-tests.md`](documents/http-tests.md).

Browser: same shop URL as HTTP; `npm ci`, `npx playwright install chromium`, `export PHOENIX_HTTP_BASE_URL=...`, then `composer test:browser`. See [`documents/browser-tests.md`](documents/browser-tests.md).

Release certification and optional checks: timed homepage smoke (`composer test:http`), carousel screenshots (`composer test:browser`), **`composer test:payment-sandbox`** ([`documents/payment-sandbox.md`](documents/payment-sandbox.md)), **`composer release-certify`** ([`documents/release-certification.md`](documents/release-certification.md)).

HTTPS: Apache **`scripts/https-server.sh`**, **`PHOENIX_HTTPS_ENABLED=1`**, **`composer test:https`**. **`composer cloud-test`** runs HTTPS last (Apache on 8443 after the `php -S` suite) when **`PHOENIX_HTTPS_ENABLED=1`** in **`.cursor/cloud.env`**. See [`documents/https-tests.md`](documents/https-tests.md).

Never add `CLAUDE.md` to the Phoenix Cart fork.
