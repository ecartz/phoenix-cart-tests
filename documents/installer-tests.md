# Installer acceptance tests

Optional **web installer wizard** coverage against a **disposable** catalog copy and a dedicated database. This suite does **not** use the shared `phoenix_test` database or the HTTP fixture shop on port 8765.

## Location and command

| Item | Value |
|------|--------|
| Directory | [`tests/installer/`](../tests/installer/) |
| Group | **`#[Group('installer')]`** |
| PHPUnit testsuite | `installer` |
| Run | **`composer test:installer`** (prepare catalog, reset DB, start server, PHPUnit, cleanup) |

The installer suite is **not** part of **`composer test:stack`**, **`composer cloud-test`**, or default GitHub Actions.

## Prerequisites

- Cloned catalog at **`PHOENIX_CART_ROOT`** (default `./PhoenixCart`)
- MySQL/MariaDB credentials (**`PHOENIX_DB_*`**) with permission to `CREATE DATABASE`
- Bash (for **`scripts/run-installer-tests.sh`**)

## Environment

| Variable | Default | Purpose |
|----------|---------|---------|
| **`PHOENIX_INSTALLER_ENABLED`** | (unset) | Must be `1` for PHPUnit to run (set by `composer test:installer`) |
| **`PHOENIX_INSTALLER_BASE_URL`** | `http://127.0.0.1:8766` | Origin of the disposable catalog server |
| **`PHOENIX_INSTALLER_CATALOG_ROOT`** | `working/installer-catalog` | Gitignored copy the installer mutates |
| **`PHOENIX_INSTALLER_DB_NAME`** | `phoenix_install` | Empty database created/dropped per run |
| **`PHOENIX_DB_HOST`**, **`PHOENIX_DB_USER`**, **`PHOENIX_DB_PASSWORD`**, **`PHOENIX_DB_PORT`** | same as integration | Server credentials for `rpc.php` and step 4 |

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

Step 1’s browser `fetch` calls are exercised directly via HttpClient (no Playwright). **`rpc.php` passes the database password in the query string** — do not log request URLs.

Install step 4 overwrites **`includes/configure.php`** and **`admin/includes/configure.php`** only under the disposable copy, never under your main **`PhoenixCart`** clone.

See [`SKIPPED.md`](../SKIPPED.md) for remaining deferred areas (admin UI beyond login smoke, payment providers, etc.).
