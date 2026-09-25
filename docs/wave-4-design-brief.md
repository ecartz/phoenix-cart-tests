# Wave 4 design brief

Locked decisions for **HTTP acceptance** tests against a running CE Phoenix Cart shop + wave 3 fixture database. PHPUnit remains the runner; no browser automation (wave 5).

**Depends on:** wave 3 `fixtures/import-mysql-fixtures.sh`, `PHOENIX_DB_*`, cloned `PhoenixCart/` at `PHOENIX_CART_ROOT`.

---

## Goals

| In scope | Out of scope (wave 5+) |
|----------|-------------------------|
| GET/POST shop URLs with real `application_top.php` | Playwright / Selenium |
| Session cookies via HTTP client cookie jar | Payment iframes / JS checkout |
| Listing, product info, cart add/buy_now | Admin tree |
| `info.php` / footer info pages from seed | Visual regression |

Wave 3 **Integration** stays the place for isolated class/SQL tests without HTTP. Wave 4 proves **segments, hooks, and templates** wired through the web entrypoints.

---

## HTTP stack

| Item | Decision |
|------|----------|
| Client | **Symfony HttpClient** (`symfony/http-client` ^7.2 dev dependency) |
| Server (local / CI) | **`php -S`** document root = catalog root (`scripts/http-server.sh`) |
| Configure | Runtime **`includes/local/configure.php`** under catalog (not committed); generated from `PHOENIX_DB_*` + `PHOENIX_HTTP_BASE_URL` |
| Skip gate | `PHOENIX_HTTP_ENABLED=1`; unset = skip Http suite (same pattern as `PHOENIX_MYSQL_ENABLED`) |

---

## PHPUnit contract

| Item | Decision |
|------|----------|
| Directory | **`tests/Http/`** |
| Group | **`#[Group('http')]`** |
| Testsuite | **`Http`** in `phpunit.xml` |
| Default `composer test` | Still **Unit only** |
| Http command | **`composer test:http`** → `--testsuite Http` |
| Full stack | **`composer test:stack`** (or extend `test:all`) → Unit + Integration + Http when env flags set |

---

## Environment variables

| Variable | Purpose | Example |
|----------|---------|---------|
| `PHOENIX_CART_ROOT` | Catalog tree | `./PhoenixCart` |
| `PHOENIX_HTTP_ENABLED` | Run Http tests | `1` |
| `PHOENIX_HTTP_BASE_URL` | Shop origin (no trailing path) | `http://127.0.0.1:8765` |
| `PHOENIX_HTTP_HOST` / `PHOENIX_HTTP_PORT` | Built-in server bind | `127.0.0.1` / `8765` |
| `PHOENIX_MYSQL_*` | Same as wave 3; required before shop answers | import fixtures first |

**Local flow:**

```bash
docker compose -f docker-compose.mysql.yml up -d
bash fixtures/import-mysql-fixtures.sh
bash scripts/http-server.sh   # background
export PHOENIX_HTTP_ENABLED=1
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
export PHOENIX_MYSQL_ENABLED=1   # for configure DB constants only
composer test:http
```

---

## Target map (by part)

| Part | Deliverables |
|------|----------------|
| **1** | `http_bootstrap`, `http_test_case`, `scripts/http-server.sh`, `index_smoke_test` (GET `/` 200 + sample catalog signal) |
| **2** | `product_info_test`, category/listing GET (`index.php`, `cPath`, sample SQL categories) |
| **3** | Cart session: `buy_now` / `shopping_cart.php` with cookie jar; assert line item from sample product |
| **4** | `info_page_test`, `fixtures/http/publish_info_pages.sql`; `cookie_usage.php` slug page; `Request` security redirects documented in `SKIPPED.md` |

**Deferred after part 4:** GDPR modules, full checkout pipeline, payment modules — wave 5 or CE-specific CI secrets.

---

## CI (follow-up)

Add `.github/workflows/phpunit-http.yml` after part 1 stabilizes: MariaDB service, import fixtures, write local configure, start `php -S` in background, run Http testsuite on PHP 8.3/8.4. Not required for part 1 land on `main`.

---

## Risks

| Risk | Mitigation |
|------|------------|
| Empty `HTTP_SERVER` redirects to installer | Always write `includes/local/configure.php` before requests |
| Windows / WSL path for built-in server | Document Linux CI as gate; WSL for local Http |
| Cookie/session isolation | New cookie jar per test class or reset session table between classes in CI |
| Catalog `configure.php` in clone | Generator overwrites only `includes/local/configure.php` |

---

## Part 1 — HTTP harness (delivered)

1. Add `symfony/http-client` to `composer.json`.
2. Add `tests/Support/http_bootstrap.php`, `http_test_case.php`.
3. Add `scripts/http-server.sh`, `fixtures/http/README.md`.
4. Add `tests/Http/index_smoke_test.php`.
5. Extend `phpunit.xml`, `composer.json` scripts.
6. Update `TESTING.md`, `AGENTS.md` pointer, `SKIPPED.md` where relevant.

---

## Part 2 — product and category GET (delivered)

1. **`product_info_test.php`** — `product_info.php?products_id=1` shows sample **Oranges** / **ORA-1**.
2. **`category_listing_test.php`** — `index.php?cPath=1` (Fruit) and `cPath=1_4` (Citrus) list sample catalog rows from `phoenix_data_sample.sql`.

---

## Part 3 — cart session (delivered)

1. **`http_bootstrap::client()`** — `max_redirects` for `Href::redirect` after actions.
2. **`shopping_cart_test.php`** — session cookie via initial GET; `index.php?action=buy_now&products_id=` sample products; assert **`shopping_cart.php`** body lists **Oranges** / **Pears**.

---

## Part 4 — info pages (delivered)

1. **`fixtures/http/publish_info_pages.sql`** — publish install slugs `privacy`, `conditions`, `shipping` so `info.php` serves them (imported with wave 3 fixtures).
2. **`info_page_test.php`** — `info.php?pages_id=` for the three footer pages; **`cookie_usage.php`** slug entry from seed HTML.
3. **`Request::check_*` redirect paths** — still out of scope; see `SKIPPED.md` (no `ssl_check.php` Http coverage).
