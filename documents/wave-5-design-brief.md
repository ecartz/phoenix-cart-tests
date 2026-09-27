# Wave 5 design brief

Locked decisions for **Playwright** browser tests against the same running shop + fixture database as wave 4. PHPUnit remains the runner for Unit, Integration, and Http; browser tests are a **separate** command.

**Depends on:** wave 3 `fixtures/import-mysql-fixtures.sh`, wave 4 `scripts/http-server.sh`, `PHOENIX_HTTP_BASE_URL`, `PHOENIX_DB_*` (shop must answer like Http acceptance).

---

## Goals

| In scope | Out of scope (wave 5 part 1 / wave 6) |
|----------|----------------------------------------|
| Real Chromium driving the catalog | Replacing Http tests with browser tests |
| JS-visible behavior (carousel slide change) | Payment iframes / sandbox secrets |
| Same fixture shop as wave 4 | Admin tree |
| | Visual screenshot diff (wave 6) |

---

## Stack

| Item | Decision |
|------|----------|
| Runner | **Playwright** (`@playwright/test`), TypeScript |
| Browser | **Chromium** only (part 1) |
| Directory | **`tests/browser/`** |
| Config | **`playwright.config.ts`** at repo root; `baseURL` from `PHOENIX_HTTP_BASE_URL` |
| Command | **`composer test:browser`** → `npx playwright test` |
| Default `composer test` | Still **Unit only** |
| Skip gate | Specs skip when **`PHOENIX_HTTP_BASE_URL`** is unset |
| Cloud | **Node 20 + Chromium libs** in [`.cursor/Dockerfile`](../.cursor/Dockerfile); `npm ci` + `playwright install` in cloud install; **`PHOENIX_BROWSER_ENABLED=1`** runs **`composer test:browser`** after **`composer test:all`** in [`cloud-test.sh`](../.cursor/scripts/cloud-test.sh) |

Gitignore: `node_modules/`, `test-results/`, `playwright-report/`.

---

## Environment

Same as wave 4 HTTP:

```bash
bash fixtures/import-mysql-fixtures.sh
bash scripts/http-server.sh
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
npm ci
npx playwright install chromium
composer test:browser
```

Playwright does **not** import SQL or start MariaDB.

---

## Part map

| Part | Deliverables |
|------|----------------|
| **1** | `package.json`, `playwright.config.ts`, `homepage_carousel.spec.ts`, `composer test:browser`, brief + `TESTING.md` |
| **2** | Navbar collapse or product option UI that requires JS |
| **3** | Checkout from scratch: cart → registration (`CHECKOUT_REDIRECT`), first click/type on customer data |
| **4** | Node + Chromium in Cloud Dockerfile (optional `composer test:browser` in cloud) |

---

## Part 1 — harness + carousel (delivered)

1. **`package.json`** + Playwright dev dependency.
2. **`playwright.config.ts`** — Chromium, `baseURL` from env.
3. **`tests/browser/homepage_carousel.spec.ts`** — click carousel **next**; assert **active** slide `innerText()` changes (Http smoke already sees fixture strings in HTML; browser proves JS transition).
4. **`composer test:browser`** script.

---

## Part 2 — navbar offcanvas (delivered)

1. **`tests/browser/navbar_offcanvas.spec.ts`** — Bootstrap **offcanvas** for the main navbar (`#collapseCoreNav`): click **`.nb-hamburger-button`**, assert **`show`** on the panel and the quick-search field is visible (JS `data-bs-toggle="offcanvas"`; Http only sees static markup).

---

## Part 3 — checkout from scratch (delivered)

CE Phoenix has **no core guest checkout**; unauthenticated checkout uses the same path as new registration (`Login::require()` → **`CHECKOUT_REDIRECT`**, **`create_account.php`** in fixture config). Add-ons that emulate guest checkout typically create then delete a customer record—the browser test does not cover that.

1. **`tests/browser/checkout_from_scratch.spec.ts`** — **`buy_now`** sample **Pears** (product **3**, no options), click cart **Checkout**, assert redirect to **`create_account.php`**, type into **`input[name="firstname"]`** (customer data module output).

---

## Part 4 — Cloud browser (delivered)

1. **[`.cursor/Dockerfile`](../.cursor/Dockerfile)** — Node.js 20, Chromium system libraries, bumped **`PHOENIX_CLOUD_ENV_REVISION`**.
2. **[`.cursor/scripts/cloud-install.sh`](../.cursor/scripts/cloud-install.sh)** — `npm ci`, `npx playwright install chromium`, writes **`PHOENIX_BROWSER_ENABLED=1`** into **`.cursor/cloud.env`**.
3. **[`.cursor/scripts/cloud-test.sh`](../.cursor/scripts/cloud-test.sh)** — after PHPUnit **`test:all`** (with HTTP server when enabled), runs **`composer test:browser`** when **`PHOENIX_BROWSER_ENABLED=1`**. Set **`PHOENIX_BROWSER_ENABLED=0`** in **`cloud.env`** to skip browser on Cloud.

---

## Wave 5 complete

**`Request::check_ssl_session_id()` on HTTPS** remains a separate TLS shop concern, not Playwright.
