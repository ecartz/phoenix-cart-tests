# Agent instructions

PHPUnit tests for CE Phoenix Cart. Exercise **real** shop classes via catalog autoload (`DIR_FS_CATALOG` + `catalog_autoloader` from `tests/bootstrap.php`). Do not reimplement Phoenix code in this repo.

## Conventions

- GPL-2.0-or-later, `declare(strict_types=1)`
- Namespace `PhoenixCart\Tests\Unit\<Area>\...`, extend `PhoenixCart\Tests\Support\PhoenixTestCase`
- Prefer data providers; assert behavior, not source text
- If a constructor needs config constants, `define()` them in the test — do not stand up MySQL
- Run `vendor/bin/phpunit` after each new test class; fix failures before committing
- Do not modify files under `PhoenixCart/` (cloned catalog is read-only for agents)

## Phoenix architecture (for test targeting)

- **Versioned classes** — `includes/system/versioned/` (latest file wins via `class_index`): `Text`, `Guarantor`, `Href`, `Form`, `Password`, `Path`, `File`, date transformers, etc.
- **Catalog classes** — `includes/classes/`, `admin/includes/classes/`
- **Segments** — procedural snippets in `includes/system/segments/` (application, checkout, listing); reached via `application_surface` / `checkout_surface` magic classes
- **HTML / templates** — content modules `includes/modules/content/*/cm_*.php` render via `Template::map()` → `default_template::_get_template_mapping_for()` into `templates/default/` or `tpl_cm_*.php`

There is no `includes/functions/` tree in current Phoenix; legacy procedural helpers are versioned classes or segments.

## Night-one scope

**In scope:** pure unit tests for classes that do not need a live database or full `application_top.php`.

**Out of scope (skip and list in `SKIPPED.md`):**

- MySQL, `database_core`, `mysql_session`, `$GLOBALS['db']`
- Checkout/application segments that assume a running shop session
- Installer, admin HTTP, payment modules
- Booting `application_top.php`

Content-module `execute()` that only buffers a `tpl_` file is allowed with stub `$GLOBALS['Template']` / product arrays.

## Parallel agent areas

| Area | Directory | Example targets |
|------|-----------|-----------------|
| Html | `tests/Unit/Html/` | `Href`, `html_element`, `Form`, `Input`, `Select`, `Image`, `Button` |
| Support | `tests/Unit/Support/` | `Date`, transformers, `Password`, `Path`, `File`, `cc_validation`, `url_query` |
| Template | `tests/Unit/Template/` | `default_template` mapping, `Template` block/content helpers without `MODULE_*` DB constants |

## Cursor Cloud specific instructions

Environment install clones `https://github.com/CE-PhoenixCart/PhoenixCart.git` into `./PhoenixCart` when missing, then runs `composer install`.

`tests/bootstrap.php` registers normalized autoload aliases for camelCase versioned classes (e.g. `Text` → `text`) so PHPUnit can load them without full `application_top.php`.

Verify with:

```bash
composer install
[ -d PhoenixCart/includes/system/autoloader.php ] || git clone --depth 1 https://github.com/CE-PhoenixCart/PhoenixCart.git PhoenixCart
vendor/bin/phpunit
```

Never add `CLAUDE.md` to the Phoenix Cart fork.
