# Wave 3 design brief

Locked decisions for implementing real-MySQL PHPUnit in `phoenix-cart-tests`. This document is the sole input for the Wave 3 implementation plan; no open product choices remain.

**Catalog pin:** CE-PhoenixCart release tip used for discovery (`install/phoenix.sql` from sibling `PhoenixCart/`). Refresh fixtures when upstream tags a new CE release.

---

## Fixture strategy

**Chosen approach: A — full upstream `phoenix.sql` import per test job.**

| Approach | Maintenance | CI time | Fidelity | Verdict |
|----------|-------------|---------|----------|---------|
| **A. Full `phoenix.sql`** | Re-copy file from catalog tag when CE releases; single source of truth | ~310 KB / 62 tables / 1,137 `INSERT` statements; import under ~30s on MariaDB 10.x | Hooks + 563 `configuration` rows + tax/geo seed match production install | **Selected** |
| **B. Schema extract + curated seed** | Must regenerate FK order; duplicate 563 config keys by hand | Smaller import | Risk drift from real install | Rejected for part 1 |
| **C. Hybrid (install + sample)** | Re-copy both files from catalog tag when CE releases | Base import + ~14 KB sample file | Matches installer “Import Sample Data”; real categories/products | **Selected for product/listing tests (parts 2+)** |

**Evidence (upstream `PhoenixCart/install/phoenix.sql`):**

- 62 `CREATE TABLE` statements; all tables use `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`.
- Critical seed counts: `configuration` 563, `hooks` 86, `countries` 251, `zones` 181, `tax_class` 1, `tax_rates` 1, `geo_zones` 1, `zones_to_geo_zones` 1, `pages` 5, `pages_description` 5, `languages` 1.
- **No demo products** in `phoenix.sql` alone (`products` / `products_description` have schema only, zero `INSERT`). Product/listing tests import CE **`install/phoenix_data_sample.sql`** (vendored as **`fixtures/phoenix_data_sample.sql`**) **after** `phoenix.sql`, same order as the installer’s “Import Sample Data” option.
- Florida tax chain for `Tax::fetch`: `tax_rates` 7.0% on class 1; `zones_to_geo_zones` links country **223**, zone **18**, geo_zone **1**; `configuration` sets `STORE_COUNTRY=223`, `STORE_ZONE=18`.

**Repo layout:**

| Path | Role |
|------|------|
| `fixtures/phoenix.sql` | Vendored copy of catalog `install/phoenix.sql` at a documented CE tag (not symlink; CI clones catalog separately but imports **this** file for reproducibility) |
| `fixtures/phoenix_data_sample.sql` | Vendored copy of catalog `install/phoenix_data_sample.sql`; import **after** `phoenix.sql` when Integration tests need sample categories/products (part 2+) |
| `fixtures/README.md` | Tag pin, refresh command, charset note |

**Refresh procedure:** On CE release tag `T`, copy `PhoenixCart/install/phoenix.sql` → `fixtures/phoenix.sql` and `PhoenixCart/install/phoenix_data_sample.sql` → `fixtures/phoenix_data_sample.sql`, commit with note `Fixture from CE tag T`. Diff upstream install when catalog releases touch schema or sample data.

**Engine:** MariaDB **10.11** service in CI (utf8mb4 compatible). MySQL 8.0 acceptable locally if charset matches.

---

## Test harness

**Bootstrap tier: T1 (minimal DB + `read_configuration`).**

| Tier | Use in Wave 3 |
|------|----------------|
| **T1** | `define DB_*` from env → catalog autoload (already in `tests/bootstrap.php`) → `new Database()` → `$GLOBALS['db'] = $db` → `require read_configuration.php` | **Part 1 default** |
| **T2** | T1 + real `hooks` table + `$hooks->register('system')` + iterate `startApplication` callbacks | **Part 2+** when a test needs hook order (e.g. `currencies::set_currency`) |
| **T3** | Partial/full `application_top.php` | **Out of part 1**; wave 4+ / cart/session integration |

**Rationale:** `Tax::fetch`, `abstract_module::check()`, and `database_core::perform` need real `mysqli` and seeded tables, not hook pipeline. T1 loads the same `MODULE_*` / `STORE_*` constants as production after full SQL import.

**Session:** **Plain PHP session** from [`tests/bootstrap.php`](../tests/bootstrap.php). Do **not** run `start_session` segment or `mysql_session` in part 1 (avoids `sessions` table writes and cookie/spider branches).

**Configure source:** **`tests/Support/mysql_bootstrap.php`** (new) defines `DB_SERVER`, `DB_SERVER_USERNAME`, `DB_SERVER_PASSWORD`, `DB_DATABASE`, and minimal catalog constants (`HTTP_SERVER`, `DIR_WS_CATALOG`, `DIR_FS_CATALOG` already set) from environment. No committed `includes/local/configure.php` in the catalog tree.

**Support classes (implementation backlog):**

| Class | Responsibility |
|-------|----------------|
| `mysql_database_helper` | Connect `Database`, assign `$GLOBALS['db']`, optional `read_configuration`, optional import SQL file once per process |
| `mysql_test_case` | Extends `phoenix_test_case`; `setUpBeforeClass` ensures DB reachable when `PHOENIX_MYSQL_ENABLED=1`; `setUp` runs T1 bootstrap; **no truncate** in part 1 (read-only tests); later parts may `DROP DATABASE` / re-import or use transactions where safe |

**Teardown:** Part 1 tests are read-only except one `perform` insert test that deletes its row in `tearDown`. Full schema reset = re-import `fixtures/phoenix.sql` in CI job setup, not per test class.

---

## PHPUnit contract

| Item | Decision |
|------|----------|
| Directory | **`tests/Integration/`** for all `@group mysql` tests |
| Group | **`#[Group('mysql')]`** on Integration classes |
| `phpunit.xml` | Second testsuite `Integration` → `tests/Integration`; keep `Unit` → `tests/Unit` |
| Fast default | **`composer test`** runs **`vendor/bin/phpunit --testsuite Unit`** (308 tests today, no DB) |
| MySQL suite | **`vendor/bin/phpunit --testsuite Integration`** or **`--group mysql`** |
| Extension | **`mysqli`** required for Integration suite (host PHP 8.4 Windows install checked: **not present** — use Docker Compose or CI) |

---

## Runtime matrix

**CI ownership:** Workflow in **`phoenix-cart-tests/.github/workflows/phpunit-mysql.yml`** (first). Follow-up: CE-PhoenixCart workflow that clones this repo at tag/main and runs the same job when upstream wants a merge gate.

**Job sketch:**

1. `services: mariadb:10.11` with env `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE=phoenix_test`, `MYSQL_USER=phoenix`, `MYSQL_PASSWORD=phoenix`.
2. Healthcheck `mysqladmin ping`.
3. Checkout `phoenix-cart-tests`; `composer install`.
4. Clone CE-PhoenixCart shallow to `./PhoenixCart` (or use submodule later).
5. `mysql … < fixtures/phoenix.sql` (database empty beforehand).
6. Env: `PHOENIX_CART_ROOT`, `PHOENIX_MYSQL_ENABLED=1`, `PHOENIX_DB_*` pointing at service.
7. `vendor/bin/phpunit --testsuite Integration` on PHP **8.3** and **8.4** matrix.

**Environment variables:**

| Variable | Purpose | CI example |
|----------|---------|------------|
| `PHOENIX_CART_ROOT` | Catalog tree | `$GITHUB_WORKSPACE/PhoenixCart` |
| `PHOENIX_MYSQL_ENABLED` | `1` = run Integration; unset = skip Integration locally | `1` |
| `PHOENIX_DB_HOST` | mysqli host | `127.0.0.1` / service name `mariadb` |
| `PHOENIX_DB_PORT` | Port | `3306` |
| `PHOENIX_DB_NAME` | Database | `phoenix_test` |
| `PHOENIX_DB_USER` | User | `phoenix` |
| `PHOENIX_DB_PASSWORD` | Password | `phoenix` |

**Local development:**

| Environment | Path |
|-------------|------|
| **Linux / WSL / macOS** | Docker Compose (`docker-compose.mysql.yml` in repo): MariaDB + optional one-shot import; PHP on host with `php-mysql` |
| **Windows host (`C:/php`)** | No `mysqli` in default install — use **WSL** or **Compose**; run Integration there |
| **Cursor Cloud** (current [`.cursor/Dockerfile`](../.cursor/Dockerfile)) | **Integration CI-only until** image adds `php-mysql` + sidecar DB; agents run **`--testsuite Unit`** only |

**Failure policy:** If `PHOENIX_MYSQL_ENABLED=1` and connection fails → **fail tests**. If unset → **`mysql_test_case` skips** Integration classes with message (local fast path).

---

## Target SQL dependency map

| Target | SQL features | Tables (minimum) | Bootstrap | Mockdb today? |
|--------|--------------|------------------|-----------|---------------|
| DB smoke | `SELECT`, row counts | any | T1 | N/A |
| `Tax::fetch` / `get` | JOIN, `mysqli_num_rows`, `fetch_all` on result | `tax_rates`, `zones_to_geo_zones`, `geo_zones`, `tax_class` | T1 + constants `TEXT_UNKNOWN_TAX_RATE` | `fetch_classes` only |
| `abstract_module::check()` | `mysqli_num_rows` | `configuration` | T1 | No |
| `database_core::perform` | INSERT | `configuration` (writable) | T1 | No |
| `abstract_module::install` / `remove` | `perform`, DELETE, multi-row | `configuration`, groups | T1 + T2 optional | No |
| `info_pages::get_pages` | JOIN, session `languages_id` | `pages`, `pages_description` | T1 + `$_SESSION['languages_id']=1` | partial mock |
| `cm_login_form::login` | dynamic customer read, update | `customers`, `customer_data` | T2+ customer bootstrap | No |
| GDPR `cm_*` | many tables, customer session | `customers_gdpr`, orders, … | T3 | No |
| Listing / cart content | `splitPageResults`, cart | products*, basket | T3 + sample SQL | No |

**Content module cost (later slices):**

| Rank | Module | Blocker |
|------|--------|---------|
| 1 (part 2) | `info_pages` helpers | Seed pages exist; needs session language id |
| 2 | `cm_header_breadcrumb` product path | Needs `phoenix_data_sample.sql` after base install |
| 3 | `cm_login_form` | `customer_data`, POST, messageStack |
| 4 | GDPR / navbar / listings | Session, cart, many tables |

---

## Part 1 — harness and core Integration (delivered)

**In scope:**

1. **`fixtures/phoenix.sql`** + `fixtures/README.md` (pinned tag note).
2. **`mysql_bootstrap.php`**, **`mysql_database_helper`**, **`mysql_test_case`**.
3. **`phpunit.xml`** Integration testsuite; **`composer.json`** script `test:mysql`.
4. **`.github/workflows/phpunit-mysql.yml`**.
5. **Integration tests:**
   - `database_smoke_test.php` — connect, `SELECT 1`, assert `configuration` count ≥ 500.
   - `tax_fetch_test.php` — `Tax::fetch(1, 223, 18)` rate **7.0**, description contains `FL TAX`.
   - `abstract_module_check_test.php` — e.g. `ht_robot_noindex` with known `MODULE_HEADER_TAGS_ROBOT_NOINDEX_STATUS` in DB returns check > 0.
   - `database_perform_test.php` — insert ephemeral `configuration` key via `perform`, assert via `query`, delete in `tearDown`.

**Explicit part 1 out-of-scope:**

- Checkout / order / cart segments and writes beyond one config row test.
- HTTP / browser / admin tree.
- Full `application_top.php` and `mysql_session`.
- Content modules (login, GDPR, navbar, listings).
- `Tax::fetch` with wrong zone (negative SQL edge cases) beyond happy path.
- CE-PhoenixCart upstream workflow (document as follow-up).
- Per-table schema drift assertions.

---

## Part 2 — sample SQL and catalog reads (delivered)

1. **`fixtures/phoenix_data_sample.sql`** vendored from CE `install/phoenix_data_sample.sql`.
2. **`fixtures/import-mysql-fixtures.sh`** — imports base + sample; used in CI.
3. **Integration tests:** `info_pages_test.php`, `product_fetch_name_test.php`; smoke asserts sample `products` rows.

---

## Part 3 — module writes and tax edges (delivered)

1. **`integration_throwaway_module`** — minimal real `abstract_module` subclass with ephemeral `MODULE_PHOENIX_INTEGRATION_PROBE_*` keys.
2. **`abstract_module_install_remove_test.php`** — `install()` writes rows; `remove()` deletes all module keys.
3. **`tax_fetch_test.php`** — zero rate + `TEXT_UNKNOWN_TAX_RATE` when zone/country does not match install geo seed.

---

## Part 4 (remaining wave 3)

Wave 3 is planned as **four commits on `main`**: parts 1–3 delivered; **part 4 left**.

| Part | Deliverables |
|------|----------------|
| **4** | One content module with real rows (`cm_login_form` or breadcrumb product path); customer seed from sample SQL where applicable |

**Beyond part 4 (wave 4 or late wave 3):** GDPR, listings, cart persistence; coordinate with HTTP acceptance.

---

## Risks and conventions

| Risk | Mitigation |
|------|------------|
| **`phoenix.sql` drift** | Pin in `fixtures/README.md`; refresh on CE tags; CI imports vendored copy |
| **PHP versions** | Integration CI: **8.3 + 8.4**; catalog lint still 7.4–8.2 — do not run mysql suite on 7.4 until catalog requires it |
| **Windows mysqli** | Document Compose; default `composer test` excludes Integration |
| **Cloud agents** | No MariaDB in current Dockerfile — Unit suite only |
| **Constants after T1** | Define language constants (`TEXT_UNKNOWN_TAX_RATE`, etc.) in test or small `fixtures/wave3-constants.php` require if missing after `read_configuration` |
| **mysqli_num_rows** | Must use real `Database` connection; mock stays wave 2b |

---

## Evidence appendix

**Bootstrap spike (code-level, T1):**

```text
putenv PHOENIX_DB_* 
require tests/bootstrap.php          // autoload, DIR_FS_CATALOG
require mysql_bootstrap.php        // DB_* defines
$db = new Database();
$GLOBALS['db'] = $db;
require …/read_configuration.php   // 563 keys defined
Tax::fetch(1, 223, 18)             // uses STORE_*-aligned seed
```

T2 spike: after import, `$hooks = new hooks('shop'); $hooks->register('system');` then foreach `$hooks->generate('startApplication')` as `application_top.php` — loads session/cart unless filtered; **not used in part 1**.

**Sample local env (Compose):**

```bash
export PHOENIX_MYSQL_ENABLED=1
export PHOENIX_CART_ROOT=/path/to/PhoenixCart
export PHOENIX_DB_HOST=127.0.0.1
export PHOENIX_DB_PORT=3306
export PHOENIX_DB_NAME=phoenix_test
export PHOENIX_DB_USER=phoenix
export PHOENIX_DB_PASSWORD=phoenix
vendor/bin/phpunit --testsuite Integration
```

---

## Part 1 checklist (delivered)

Ordered checklist for part 1 (all done in commit `a48fc13` on `main`):

1. Add `fixtures/phoenix.sql` (copy from catalog) + `fixtures/README.md`.
2. Add `tests/Support/mysql_bootstrap.php`, `mysql_database_helper.php`, `mysql_test_case.php`.
3. Extend `phpunit.xml` (Integration testsuite); update `composer.json` scripts (`test`, `test:mysql`).
4. Add `tests/Integration/database_smoke_test.php`.
5. Add `tests/Integration/tax_fetch_test.php`.
6. Add `tests/Integration/abstract_module_check_test.php`.
7. Add `tests/Integration/database_perform_test.php`.
8. Add `.github/workflows/phpunit-mysql.yml`.
9. Add optional `docker-compose.mysql.yml` for local import.
10. Update `TESTING.md` / `AGENTS.md` / `README.md` with env contract and commands.
11. Run Integration suite in CI; keep Unit suite green on hosts without mysqli.
