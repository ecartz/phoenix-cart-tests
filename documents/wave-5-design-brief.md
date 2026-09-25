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
| Cloud | No Node in `.cursor/Dockerfile` until part 4; **`composer cloud-test`** stays PHPUnit-only |

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
| **3** | Guest checkout through first click/type step |
| **4** | Node + Chromium in Cloud Dockerfile (optional `composer test:browser` in cloud) |

---

## Part 1 — harness + carousel (delivered)

1. **`package.json`** + Playwright dev dependency.
2. **`playwright.config.ts`** — Chromium, `baseURL` from env.
3. **`tests/browser/homepage_carousel.spec.ts`** — sample carousel has **Our Farm** and **Strawberries Coming Soon**; click **next**; assert **active** slide text changes (Http smoke already sees both strings in HTML; browser proves visibility/transition).
4. **`composer test:browser`** script.

---

## Parts 2–4 (remaining)

See table above. **`Request::check_ssl_session_id()` on HTTPS** remains wave 5+ / separate from Playwright until TLS shop setup exists.
