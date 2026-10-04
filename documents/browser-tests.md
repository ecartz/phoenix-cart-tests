# Browser tests

Browser tests use **Playwright** (TypeScript) against the same fixture shop URL as HTTP acceptance tests. They cover behavior that depends on **JavaScript**, layout, or client-side UI — not duplicated by Symfony HttpClient alone.

## Location and command

| Item | Value |
|------|--------|
| Directory | [`tests/browser/`](../tests/browser/) |
| Config | [`playwright.config.ts`](../playwright.config.ts) (`baseURL` from **`PHOENIX_HTTP_BASE_URL`**) |
| Run | **`composer test:browser`** |

Playwright does not import SQL or start MariaDB; start the shop the same way as for HTTP tests ([`http-tests.md`](http-tests.md)).

## Prerequisites

```bash
bash fixtures/import-mysql-fixtures.sh
bash scripts/http-server.sh
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
npm ci
npx playwright install chromium
composer test:browser
```

Specs skip when **`PHOENIX_HTTP_BASE_URL`** is unset.

## Specs

| Spec | Behavior exercised |
|------|---------------------|
| `homepage_carousel.spec.ts` | Carousel **next** control changes the active slide text |
| `navbar_offcanvas.spec.ts` | Bootstrap offcanvas opens from the hamburger control |
| `checkout_from_scratch.spec.ts` | `buy_now` → cart → checkout redirect to `create_account.php`; first customer field |
| `homepage_visual.spec.ts` | Carousel **`toHaveScreenshot`** baseline |
| `currency_dropdown.spec.ts` | Navbar **EUR** currency dropdown updates URL and product price |
| `search_form.spec.ts` | Navbar **quick_find** submits keywords to **`advanced_search_result.php`** |
| `products_new.spec.ts` | **`products_new.php`** sortable listing shows sample catalog products |
| `product_attribute_select.spec.ts` | Lemons **Box Size** select chooses the **24** option |

Binding names in specs use **snake_case** (enforced by [`browser_spec_snake_case_test.php`](../tests/unit/invariants/browser_spec_snake_case_test.php)).

## Screenshot baselines

Commit PNGs under **`tests/browser/*.spec.ts-snapshots/`** (for example **`homepage-carousel-chromium-linux.png`**). CI and release certification run on Linux; refresh baselines there:

```bash
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
npx playwright test tests/browser/homepage_visual.spec.ts --update-snapshots
```

**`npm run test:update-snapshots`** updates all browser specs.

## Cloud and CI

**`composer cloud-test`** and GitHub Actions [`.github/workflows/phpunit-mysql.yml`](../.github/workflows/phpunit-mysql.yml) run **`composer test:browser`** after **`composer test:stack`** when browser is enabled (see [`.cursor/cloud.env`](../.cursor/cloud.env) **`PHOENIX_BROWSER_ENABLED`** on Cloud).
