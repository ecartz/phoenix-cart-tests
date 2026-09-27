# Testing strategy for CE Phoenix Cart

This repository holds **all** automated tests for [CE-PhoenixCart](https://github.com/CE-PhoenixCart/PhoenixCart). The catalog repo contains **functional code only**; it does not ship PHPUnit, test classes, or fixture data.

CE-PhoenixCart CI runs this suite against the catalog revision under test. This repo stays public so upstream can clone it without secrets.

## How CI selects a test revision

| PhoenixCart context | Test repo git ref |
|---------------------|-------------------|
| **Tagged release** (every CE release is tagged) | Same tag on `phoenix-cart-tests` if that tag exists |
| **Everything else** (PRs, branch pushes, untagged commits) | `main` |

Local and cloud runs set `PHOENIX_CART_ROOT` to the catalog tree (sibling `../PhoenixCart`, `./PhoenixCart`, or env override). See `tests/bootstrap.php`. The reference catalog for local runs and merge gates is **CE-PhoenixCart 1.1.0.8** (or newer CE checkout with compatible Html typing on PHP 8.4).

## Wave status

### Wave 1 — complete

**Goal:** Exercise **pure logic** through the real catalog autoloader without booting the shop.

**Tooling:** PHPUnit only.

**Bootstrap:** `catalog_autoloader` + normalized aliases for camelCase versioned classes. No `application_top.php`, no `$GLOBALS['db']`.

**Typical targets:**

- Versioned helpers (`Text`, `Guarantor`, `Password`, `Path`, `File`, date transformers, `url_query`, `cc_validation`, …)
- HTML builders (`Href`, `Form`, `Input`, `Select`, …)
- `default_template` mapping and parts of `Template` that do not call `build_blocks()` or live module lists
- Catalog classes that only need autoload (`breadcrumb`, …)

**Deliberate exclusions** are listed in [`SKIPPED.md`](SKIPPED.md).

Wave 1 covers a **small fraction** of autoloaded types (~10% by name). That is expected: most Phoenix behavior is configuration-, session-, or database-driven.

### Wave 2 — complete (stable-catalog focus)

**Goal:** Close remaining **pure** versioned helpers and Html edge cases that need isolated constants (`RunInSeparateProcess`), plus content-module `execute()` paths that buffer `tpl_` files with stub `$GLOBALS['Template']` / `Linker` / `messageStack` and hand `define()` for module keys only.

**Delivered (representative):**

- Support: `Search`, `Request`, `messageStack` / `alertBlock`, `old_password`, `Versions`, expanded date transformers and `page_selection`
- Html: `Href` when `SESSION_FORCE_COOKIE_USE === 'True'`, `Image` when `IMAGE_REQUIRED === 'false'` (separate process)
- Content: shared [`content_module_test_case`](tests/Support/content_module_test_case.php); thin modules under `tests/Unit/Content/` (footer/header/login/CAS/info/index/cart/testimonials/checkout-success titles and related stubs)
- Support stretch: `modular::display_layout()`, `navigationHistory` snapshot/path helpers
**Wave 2 completion checklist:**

- [x] All **wave 2** rows in [`SKIPPED.md`](SKIPPED.md) covered or re-tagged
- [x] Stable versioned targets from the wave 2 inventory have PHPUnit classes under `tests/Unit/`
- [x] Full suite green on PHP 8.3/8.4 against a **CE-PhoenixCart** checkout (`PHOENIX_CART_ROOT`)

**Selecting “stable” catalog classes for tests:** prefer versioned files whose latest `class_index` winner has not changed in git since roughly Sep 2024; avoid churny Html/Select paths unless fixing upstream first.

### Wave 2b — complete (mock database, still no MySQL)

**Goal:** Install `$GLOBALS['db']` as an in-memory double, seed configuration (and simple table) rows, run production `read_configuration`, then exercise config-driven and single-table paths without a real engine.

**Delivered:**

- Support: [`mock_catalog_database`](tests/Support/mock_catalog_database.php) (case-insensitive `FROM` matching), [`mock_catalog_query_result`](tests/Support/mock_catalog_query_result.php) (`fetch_assoc()`), [`configuration_test_helper`](tests/Support/configuration_test_helper.php) (sets `$GLOBALS['db']` + requires `read_configuration.php`)
- Configuration / Template: `read_configuration_test`, enabled `build_blocks` via `ht_robot_noindex` / `ht_table_click_jquery` / `ht_canonical` / `ht_pages_seo` / `ht_category_title` / `bm_home`, `template_content_modules_mockdb_test`
- Catalog helpers: `country_test`, `zone_test`, `tax_test` (`fetch_classes` / `get_class_title`), `currencies_test`, `language_test`, `product_test` (`fetch_name`), `info_pages_test`, `abstract_module_enabled_test`
- Content: `cm_header_breadcrumb_test` (Schema; product / category stub / manufacturer stub)

**Wave 2b completion checklist:**

- [x] Mock supports `fetch_all(string)` and `query()->fetch_assoc()` (not `mysqli_num_rows` / writes)
- [x] `Template::build_blocks()` with enabled real header_tags and boxes modules
- [x] `get_content_modules` loaded via mock configuration rows
- [x] `Country` / `Zone` / `Tax::fetch_classes` / `currencies` / `language` / `Product::fetch_name` / `info_pages` helpers
- [x] Explicit `abstract_module::isEnabled()` via mock STATUS
- [x] `cm_header_breadcrumb` Schema paths (including stubbed category/manufacturer)
- [x] Tests tagged `#[Group('mockdb')]`; full suite and `--group mockdb` green

**Still wave 3 (now covered on `main`):** `abstract_module::check()`, `Tax::fetch` joins, `install`/`remove`/`perform`, sample SQL, and breadcrumb product Integration — see **Wave 3** below.

## Configuration constants and why “wave 2” is not trivial

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
| **Minimal `configuration` (+ related) rows in a fixture DB** | Full SQL, joins, `database_core`, checkout segments | Requires database infrastructure (see wave 3); ground truth for “does the query work?” |

So the roadmap splits **“no DB process”** work from **“real SQL”** work. **Mocking the database** sits between wave 2 and wave 3: still PHPUnit-only CI, but configured constants and module strings can come from **fake query results**, not hand-wavy `define()` lists.

### Mocking the database (wave 2b)

Use [`configuration_test_helper::load_from_configuration_rows()`](tests/Support/configuration_test_helper.php) or install [`mock_catalog_database`](tests/Support/mock_catalog_database.php) via `install_as_global()`:

1. Build `configuration_key` / `configuration_value` rows (and optional `table_rows` keyed by table name).
2. Helper assigns `$GLOBALS['db']` and `require`s `read_configuration.php` so constants match production order; use `#[RunInSeparateProcess]` when redefining keys.
3. `query()` returns [`mock_catalog_query_result`](tests/Support/mock_catalog_query_result.php) with `fetch_assoc()`; `fetch_all()` accepts a string or that result. Matching is by `FROM {table}` substring only (no WHERE filtering).
4. Tag tests `#[Group('mockdb')]`. Run `vendor/bin/phpunit --group mockdb` for the mock subset.

**Covered by the mock today:** configuration load; `build_blocks` / `get_content_modules`; `Country` / `Zone` / `Tax::fetch_classes` / `currencies` / `language` / `Product::fetch_name` / `info_pages`; `cm_header_breadcrumb` Schema paths; `isEnabled()`.

**Still use real MySQL (wave 3), not mocks, when:**

- SQL is complex (joins, subqueries, dynamic fragments) and correctness of the query matters
- Code uses `mysqli_num_rows`, transactions, or `database_core` edge cases
- Checkout/order/cart segments write to multiple tables
- You want CE-PhoenixCart CI to prove the catalog works against a real engine

Mocked-db tests live under `tests/Unit/` with `#[Group('mockdb')]`. Optional later split: `tests/Integration/` for heavier mockdb / `@group mysql` for fixtures.

---

## Future waves

### Wave 2 — Pure units and controlled fakes (no database process)

**Status:** **Complete** for stable-catalog scope; see **Wave status** above. Further module-list work moves to **2b** (mock db) or **3** (MySQL).

**Goal:** Finish autoloaded helpers and template/module **units** that remain testable without SQL or a db double.

**Tooling:** PHPUnit only (optional `@runInSeparateProcess` for header/constant isolation).

**Examples:**

- Remaining Html/Support types (`Textarea`, `Tickable`, `Linker`, `named_html_element`, …)
- Content-module tests that only buffer a `tpl_` file with **hand-built** `$GLOBALS` arrays (no real `MODULE_*` from DB)
- Template tests that stub `$GLOBALS['Template']` / `hooks` without loading configuration

**Wave 2b — Mock database (still no MySQL in CI):** **Complete** — see **Wave status** above.

**Not wave 2 alone:** Hand-copying dozens of `define()` calls instead of mock + `read_configuration` — that duplicates production order and drifts quickly.

### Wave 3 — Real MySQL and fixture catalog

**Status:** **Wave 3 complete (parts 1–4)** — MySQL harness, fixtures + sample SQL, module install/remove, tax edges, and `cm_header_breadcrumb` product path Integration. Design: [`documents/wave-3-design-brief.md`](documents/wave-3-design-brief.md).

**Goal:** Test classes and segments that need **`configuration`**, products, customers, cart, tax, zones, or checkout segments — without a browser.

**Tooling:**

- PHPUnit with **`@group mysql`** (or similar)
- **Fixture SQL** stored in this repo (minimal schema + seed), not in CE-PhoenixCart
- CI: MySQL/MariaDB service container on **CE-PhoenixCart** workflow; import fixture; set `PHOENIX_CART_ROOT`

**Examples:**

- Prove **SQL and schema** behavior (not just “given these rows, PHP does X”)
- Products, customers, cart persistence, checkout segments that **write** to the database
- Regression tests where mock db would hide broken queries

**Relationship to wave 2b:** Use **mock db** for configuration-driven **unit/integration** tests fast in CI. Use **MySQL fixtures** when the **database layer** is what you are verifying. Many features may get mock-db tests first, then a thinner set of mysql tests for critical paths.

**Bridge from wave 2:** The first time you need **authentic** module lists loaded like production, prefer **mock db + `read_configuration`** (wave 2b). Move to wave 3 when mocks become unwieldy or SQL correctness is the point.

### Wave 4 — HTTP acceptance (no browser)

**Status:** **Complete (parts 1–4 + finish)** — HTTP harness, cart/info pages, redirect hardening, `ssl_check.php`, and Request user-agent/IP mismatch redirects. Design: [`documents/wave-4-design-brief.md`](documents/wave-4-design-brief.md). **Wave 5** is browser automation (Playwright, etc.).

**Goal:** Black-box **HTTP** against a running shop + fixture DB: pages, actions, segments reached via URLs.

**Tooling (choose one stack; keep runners in this repo):**

- PHPUnit + HTTP client (Symfony HttpClient / Guzzle), or
- Codeception (PhpBrowser module), or
- Behat + Mink (Goutte driver)

**Examples:**

- GET listing/product pages; POST `buy_now` / cart actions with session cookies
- Segment-driven pages (`sortable_product_listing`, …) via real requests

**Not required yet:** Selenium — server-rendered HTML and cookies are enough for most catalog/cart flows.

### Wave 5 — Browser automation (Playwright)

**Status:** **Complete (parts 1–4)** — Playwright harness, carousel, navbar offcanvas, checkout-from-scratch, Cloud Node/Chromium + optional browser in **`composer cloud-test`**. Design: [`documents/wave-5-design-brief.md`](documents/wave-5-design-brief.md).

**Goal:** Flows where **JavaScript**, layout, or payment iframes matter — not duplicated by Symfony HttpClient.

**Tooling:**

- **Playwright** (`@playwright/test`, TypeScript) in [`tests/browser/`](tests/browser/)
- **`composer test:browser`** (requires `PHOENIX_HTTP_BASE_URL`, running shop, `npm ci`, `npx playwright install chromium`)

**Examples (roadmap):**

- Carousel / navbar JS (parts 1–2)
- Cart → **`create_account.php`** via checkout login gate; first customer-data field (part 3)
- Payment modules (Stripe SCA, PayPal) — later parts / wave 6
- Admin UI, GDPR flows with client behavior

PHPUnit remains the runner for waves 1–4; browser suites run via **`composer test:browser`** (local) and via **`composer cloud-test`** when **`PHOENIX_BROWSER_ENABLED=1`** in [`.cursor/cloud.env`](.cursor/cloud.env). GitHub Actions today runs PHPUnit only (see [`.github/workflows/phpunit-mysql.yml`](.github/workflows/phpunit-mysql.yml)).

### Wave 6 — Release hardening (optional)

**Status:** **Complete (parts 1–4)** — timed HTTP smoke, Playwright carousel baseline, payment sandbox CI secrets, release certification. Design: [`documents/wave-6-design-brief.md`](documents/wave-6-design-brief.md).

**Part 1:** `GET /` must return **200** within **`PHOENIX_HTTP_BUDGET_SECONDS`** (default **10**). Runs with **`composer test:http`** / **`composer test:all`** when **`PHOENIX_HTTP_ENABLED=1`**.

**Part 2:** **`homepage_visual.spec.ts`** compares the homepage carousel to committed Playwright snapshots. Runs with **`composer test:browser`**. To create or refresh baselines on **Linux** (shop + fixtures running):

```bash
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
npx playwright test tests/browser/homepage_visual.spec.ts --update-snapshots
```

Commit the PNG under **`tests/browser/homepage_visual.spec.ts-snapshots/`** (e.g. **`homepage-carousel-chromium-linux.png`**). **`npm run test:update-snapshots`** updates all browser specs; prefer the scoped command above for part 2 only.

**Part 3:** Optional **`composer test:payment-sandbox`** when **`PHOENIX_PAYMENT_SANDBOX_ENABLED=1`** and Stripe test keys are set (GitHub secrets; see [`documents/payment-sandbox-ci.md`](documents/payment-sandbox-ci.md)).

**Part 4:** **`composer release-certify`** checks out CE-PhoenixCart at [`fixtures/catalog_pin.txt`](fixtures/catalog_pin.txt) (or **`PHOENIX_CATALOG_TAG`**) and runs the full PHPUnit stack (+ optional browser). See [`documents/release-certification.md`](documents/release-certification.md). GitHub runs the same on **tag push** via [`.github/workflows/release-certification.yml`](.github/workflows/release-certification.yml).

### Wave 7 — SSL session id (optional)

**Status:** **Complete** — Apache HTTPS acceptance for `Request::check_ssl_session_id()`. Design: [`documents/wave-7-design-brief.md`](documents/wave-7-design-brief.md).

**Skip gate:** **`PHOENIX_HTTPS_ENABLED=1`**. Also set **`PHOENIX_HTTP_ENABLED=1`** and point **`PHOENIX_HTTP_BASE_URL`** / **`PHOENIX_HTTPS_BASE_URL`** at the HTTPS shop (default `https://127.0.0.1:8443`).

**Run:** Locally: `bash scripts/https-server.sh` (Linux), then **`composer test:https`**. **`composer cloud-test`** runs the same test last on port **8443** after PHPUnit and Playwright on **8765** (requires Apache in the Cloud image; **`PHOENIX_HTTPS_ENABLED=1`** in **`.cursor/cloud.env`**). The test applies **`fixtures/http/enable_ssl_session_check.sql`**; default fixture import leaves **`SESSION_CHECK_SSL_SESSION_ID`** at install **`False`**.

---

## Tooling summary

| Wave | Primary tools | CE-PhoenixCart CI adds |
|------|----------------|-------------------------|
| 1 | PHPUnit | PHP, clone catalog + this repo |
| 2 | PHPUnit | Same as wave 1 |
| 2b | PHPUnit + **`$GLOBALS['db']` mock** | Same as wave 1 (no MySQL service) |
| 3 | PHPUnit + MySQL fixtures | DB service, import fixture SQL from this repo |
| 4 | HTTP acceptance (Guzzle / Codeception / Behat+Goutte) | DB + web server + HTTP suite |
| 5 | Playwright (`tests/browser/`) | DB + web server + Node + Chromium |
| 6 | Mixed | Secrets, optional external services |
| 7 | Apache HTTPS + curl | Opt-in Linux host; not default Cloud |

```text
Wave 1   PHPUnit, autoload only
   ↓
Wave 2   PHPUnit, stubs (no db)
   ↓
Wave 2b  PHPUnit, mock $GLOBALS['db'] → configuration / module constants
   ↓
Wave 3   PHPUnit + real MySQL (SQL, cart, checkout writes)
   ↓
Wave 4   HTTP acceptance
   ↓
Wave 5   Browser automation
   ↓
Wave 6   Visual / perf / payment sandbox
   ↓
Wave 7   SSL session id (Apache HTTPS)
```

## Repository layout (evolving)

| Path | Role |
|------|------|
| `tests/Unit/` | Waves 1–2 and early **2b** (mock-db tests live here with `#[Group('mockdb')]`, not only under `tests/Integration/`) |
| `tests/Integration/` | Additional wave 2b / wave 3 (MySQL) — *optional split later* |
| `tests/Support/` | Shared test support (for example `mock_catalog_database`, `configuration_test_helper`, `phoenix_test_case`) |
| `tests/Http/` | Wave 4 HTTP acceptance (`#[Group('http')]`) |
| `tests/Http/ssl_session_id_test.php` | Wave 7 HTTPS (`#[Group('https')]`, opt-in) |
| `tests/browser/` | Wave 5 Playwright specs |
| `playwright.config.ts` | Playwright base URL + Chromium project |
| `fixtures/` | SQL seeds (wave 3); [`fixtures/http/README.md`](fixtures/http/README.md) documents configure for wave 4 |
| `SKIPPED.md` | Explicit deferrals; add a **Wave** column when listing new skips |

## Agent and human conventions

- See [`AGENTS.md`](AGENTS.md) for PHPUnit style, namespaces, and cloud bootstrap.
- Prefer **behavior** assertions and data providers.
- Do not copy Phoenix implementation into this repo.
- When skipping, update `SKIPPED.md` with **reason** and intended **wave** (including **2b** if a db mock would unlock the test).
- For configuration-dependent behavior, prefer **mock db + `read_configuration` (wave 2b)** or **fixture MySQL (wave 3)** over long manual `define()` lists.

## Running locally

```bash
composer install
export PHOENIX_CART_ROOT=/path/to/PhoenixCart   # optional
vendor/bin/phpunit
```

Clone upstream catalog if needed:

```bash
git clone --depth 1 https://github.com/CE-PhoenixCart/PhoenixCart.git PhoenixCart
```

Windows developers may see path-separator or PHP version differences versus Linux CI; treat Linux CI on CE-PhoenixCart as the merge gate once workflows exist.
