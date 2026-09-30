# Installer acceptance tests

Optional **web installer wizard** coverage against a **disposable** catalog copy and a dedicated database. This suite does **not** use the shared `phoenix_test` database or the HTTP fixture shop on port 8765.

## Location and command

| Item | Value |
|------|--------|
| Directory | [`tests/installer/`](../tests/installer/) |
| Group | **`#[Group('installer')]`** |
| PHPUnit testsuite | `installer` |
| Run | **`composer test:installer`** (prepare catalog, reset DB, start server, PHPUnit, cleanup) |

The installer suite is **not** part of **`composer test:stack`** or **`composer cloud-test`**. GitHub Actions runs it as the parallel **`installer`** job in [`.github/workflows/phpunit-mysql.yml`](../.github/workflows/phpunit-mysql.yml) via **`composer test:installer`** (separate from the **`full-stack`** job so Playwright failures do not hide installer results).

## Prerequisites

- Cloned catalog at **`PHOENIX_CART_ROOT`** (default `./PhoenixCart`)
- MySQL/MariaDB credentials (**`PHOENIX_DB_*`**) with permission to `CREATE DATABASE`, or local **`mysql -u root`** socket access for **`scripts/reset-installer-database.sh`** (MariaDB rejects TCP `root@127.0.0.1` when `unix_socket` auth is enabled). On GitHub Actions, set **`PHOENIX_MYSQL_ROOT_PASSWORD`** (the workflow uses `root` against the MariaDB service) so reset connects over TCP and grants **`phoenix@%`** on **`phoenix_install`**
- Bash (for **`scripts/run-installer-tests.sh`**)

## Environment

| Variable | Default | Purpose |
|----------|---------|---------|
| **`PHOENIX_INSTALLER_ENABLED`** | (unset) | Must be `1` for PHPUnit to run (set by `composer test:installer`) |
| **`PHOENIX_INSTALLER_BASE_URL`** | `http://127.0.0.1:8766` | Origin of the disposable catalog server |
| **`PHOENIX_INSTALLER_CATALOG_ROOT`** | `working/installer-catalog` | Gitignored copy the installer mutates |
| **`PHOENIX_INSTALLER_DB_NAME`** | `phoenix_install` | Empty database created/dropped per run |
| **`PHOENIX_DB_HOST`**, **`PHOENIX_DB_USER`**, **`PHOENIX_DB_PASSWORD`**, **`PHOENIX_DB_PORT`** | same as integration | Server credentials for `rpc.php` and step 4 |
| **`PHOENIX_MYSQL_ROOT_PASSWORD`** | (unset) | When set, **`reset-installer-database.sh`** and PHP reset use TCP **`root`** and grant **`phoenix@%`** (GitHub Actions); when unset, socket **`root`** and **`phoenix@localhost`** (Cloud/local MariaDB) |

## Manual run

```bash
bash scripts/prepare-installer-catalog.sh
bash scripts/reset-installer-database.sh
bash scripts/installer-server.sh   # separate terminal
export PHOENIX_INSTALLER_ENABLED=1
export PHOENIX_INSTALLER_BASE_URL=http://127.0.0.1:8766
vendor/bin/phpunit --testsuite installer
```

## What is tested

[`install_wizard_test.php`](../tests/installer/install_wizard_test.php) drives `install/rpc.php` (`dbCheck`, `dbImport` with sample catalog data), POSTs installer steps 2–4, asserts the storefront serves sample categories, and verifies the wizard-created administrator can log into **`admin/login.php`**.

[`admin_catalog_test.php`](../tests/installer/admin_catalog_test.php) reuses the shared wizard helper ([`installer_wizard`](../tests/support/installer_wizard.php)) in `setUpBeforeClass`, then exercises authenticated admin HTTP against the disposable shop:

- **`/admin/index.php`** — dashboard (`display-4`)
- **`/admin/customers.php`** — customer list
- **`/admin/orders.php`** — order list
- **`/admin/catalog.php`** — sample catalog listing (`Citrus Fruit` under `cPath=1`)
- **`catalog.php?action=set_flag`** — toggles a sample product inactive and back (GET, as in the admin UI)

[`admin_pages_test.php`](../tests/installer/admin_pages_test.php) runs the same wizard setup, then covers additional read-only admin pages and one configuration write:

- **`/admin/configuration.php?gID=1`** — store name from the installer
- **`/admin/languages.php`**, **`/admin/countries.php`**, **`/admin/administrators.php`**
- **`/admin/modules.php?set=payment`** — sample payment modules
- **`configuration.php?action=save`** — renames the store, asserts the change, then restores the original name

[`admin_localization_test.php`](../tests/installer/admin_localization_test.php) covers localization, tax, order status, catalog metadata, and shipping modules, plus a reversible specials status toggle:

- **`/admin/currencies.php`**, **`/admin/zones.php`**, **`/admin/tax_classes.php`**, **`/admin/tax_rates.php`**, **`/admin/geo_zones.php`**, **`/admin/orders_status.php`**
- **`/admin/manufacturers.php`**, **`/admin/reviews.php`**, **`/admin/specials.php`**
- **`/admin/modules.php?set=shipping`** — flat-rate shipping module
- **`specials.php?action=set_flag`** — toggles the sample special inactive and back (GET, as in the admin UI)

[`admin_merchandising_test.php`](../tests/installer/admin_merchandising_test.php) covers merchandising, content, and order-total modules, plus a reversible advert status toggle:

- **`/admin/advert_manager.php`**, **`/admin/products_attributes.php`**, **`/admin/products_expected.php`**
- **`/admin/testimonials.php`**, **`/admin/info_pages.php`**, **`/admin/customer_data_groups.php`**
- **`/admin/modules.php?set=order_total`** — sub-total order-total module
- **`advert_manager.php?action=set_flag`** — toggles the sample carousel advert inactive and back (GET, includes `formid` as in the admin UI)

Each test class runs an independent wizard install after [`install_test_case`](tests/support/install_test_case.php) resets **`phoenix_install`** (five classes → five installs per full **`composer test:installer`** run). Each test method logs in again via [`login_installed_admin()`](../tests/support/install_test_case.php) (fresh cookie jar per method). [`ensure_install_directory()`](../tests/support/installer_bootstrap.php) restores **`install/`** on the catalog copy when a prior run removed it.

Step 1’s browser `fetch` calls are exercised directly via HttpClient (no Playwright). **`rpc.php` passes the database password in the query string** — do not log request URLs.

Install step 4 overwrites **`includes/configure.php`** and **`admin/includes/configure.php`** only under the disposable copy, never under your main **`PhoenixCart`** clone.

See [`SKIPPED.md`](../SKIPPED.md) for remaining deferred areas (admin UI beyond the installer admin tests above, payment providers, etc.).
