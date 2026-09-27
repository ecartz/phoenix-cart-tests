# Wave 6 design brief

Locked decisions for **release hardening** on top of waves 3–5 (fixture MySQL, HTTP acceptance, optional Playwright). Part 4 adds **tag-paired release certification** between this repo and CE-PhoenixCart.

**Depends on:** wave 3 `fixtures/import-mysql-fixtures.sh`, wave 4 `scripts/http-server.sh`, `PHOENIX_HTTP_ENABLED`, `PHOENIX_HTTP_BASE_URL`, `PHOENIX_DB_*`.

---

## Goals

| In scope | Out of scope |
|----------|--------------|
| Timed HTTP smoke on the fixture shop (part 1) | k6 load tests |
| Playwright **toHaveScreenshot** baselines on stable catalog UI (part 2) | External visual diff SaaS |
| Payment sandbox credentials in CI secrets (part 3) | Live payment capture in default CI |
| Tagged test repo + tagged PhoenixCart certification (part 4) | Full checkout + Stripe.js iframe flows remain out of scope |

---

## Stack

| Item | Part 1 | Part 2 | Part 4 |
|------|--------|--------|--------|
| Runner | PHPUnit Http | Playwright | `composer release-certify` |
| Skip gate | `PHOENIX_HTTP_ENABLED=1` | `PHOENIX_HTTP_BASE_URL` | MySQL + HTTP (+ optional browser) |
| Pin | — | snapshot files | [`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt) |

---

## Part map

| Part | Deliverables |
|------|----------------|
| **1** | `homepage_timing_test.php`, brief + `TESTING.md` |
| **2** | `homepage_visual.spec.ts`, Playwright screenshot config, `npm run test:update-snapshots` |
| **3** | `payment_sandbox_bootstrap.php`, `apply-payment-sandbox-config.php`, `payment_sandbox_stripe_config_test.php`, `payment-sandbox-ci.md`, GitHub workflow |
| **4** | `catalog_pin.txt`, `release-certification.sh`, `release-certification.md`, GitHub workflow, `composer release-certify` |

---

## Part 1 — timed homepage smoke (delivered)

1. **`tests/Http/homepage_timing_test.php`** — `GET /`, assert **200**, assert Symfony **`total_time`** &lt; budget.

---

## Part 2 — homepage carousel baseline (delivered)

1. **`tests/browser/homepage_visual.spec.ts`** — carousel **`toHaveScreenshot`** baseline.
2. **`playwright.config.ts`** — shared **`expect.toHaveScreenshot`** options.
3. **`package.json`** — **`test:update-snapshots`** script.

**Bootstrap baselines (Linux):** with fixtures imported and **`scripts/http-server.sh`** running:

```bash
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
npx playwright test tests/browser/homepage_visual.spec.ts --update-snapshots
```

Commit the generated PNG under **`tests/browser/homepage_visual.spec.ts-snapshots/`** (e.g. **`homepage-carousel-chromium-linux.png`**). Release certification runs on Ubuntu; do not commit a Windows baseline from a dev host.

---

## Part 3 — payment sandbox CI (delivered)

1. **[`tests/Support/payment_sandbox_bootstrap.php`](../tests/Support/payment_sandbox_bootstrap.php)** — apply Stripe SCA **test** keys from env to `configuration`.
2. **[`scripts/apply-payment-sandbox-config.php`](../scripts/apply-payment-sandbox-config.php)** — CLI entry (also run from fixture import when **`PHOENIX_PAYMENT_SANDBOX_ENABLED=1`**).
3. **[`tests/Http/payment_sandbox_stripe_config_test.php`](../tests/Http/payment_sandbox_stripe_config_test.php)** — `#[Group('payment_sandbox')]`; **`composer test:payment-sandbox`**.
4. **[`documents/payment-sandbox-ci.md`](payment-sandbox-ci.md)** — GitHub secret names and runbook.
5. **[`.github/workflows/payment-sandbox.yml`](../.github/workflows/payment-sandbox.yml)** — manual dispatch with secrets.

---

## Part 4 — release certification (delivered)

1. **[`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt)** — documents vendored fixture CE tag.
2. **[`scripts/release-certification.sh`](../scripts/release-certification.sh)** — checkout **PhoenixCart** at pin/`PHOENIX_CATALOG_TAG`, import fixtures, run **`composer test:all`** (+ optional **`test:browser`**).
3. **[`documents/release-certification.md`](release-certification.md)** — tag pairing and publish steps.
4. **[`.github/workflows/release-certification.yml`](../.github/workflows/release-certification.yml)** — runs on **git tag push** (and manual dispatch).
5. **`composer release-certify`** — local/Cloud entry point.

---

## Wave 6 status

Parts **1–4** delivered.
