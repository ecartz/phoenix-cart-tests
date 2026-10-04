# Coverage gaps (behavior harness)

This page tracks **known gaps** between CE Phoenix Cart behavior and what the harness asserts today. It is not PHPUnit line coverage. When a gap closes, narrow or remove the row and point to the test in [`http-tests.md`](http-tests.md), [`installer-tests.md`](installer-tests.md), or [`browser-tests.md`](browser-tests.md).

Hosted PayPal, Stripe, and 2Checkout checkout remain out of scope — see [`SKIPPED.md`](../SKIPPED.md).

Merged HTTP/installer coverage from [`cursor/remaining-coverage-gaps-a33a`](https://github.com/ecartz/phoenix-cart-tests) supersedes the old **First tests worth adding** items 1–4 (priced-attribute checkout, invoice qty 2, storefront hook fixture, tightened login/search/shipping/contact/security). See the **Recently closed** section below.

## HTTP storefront — checkout, money, and tax

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Hosted card/wallet checkout | Not attempted | Out of scope — [`SKIPPED.md`](../SKIPPED.md) |

Representative closed behavior (see [`http-tests.md`](http-tests.md)): COD / money-order stored totals and **`payment_method`**; priced attribute qty **2**; free shipping **`ot_shipping` 0**; item/zone/table/geo shipping matrix; tax-inclusive display; session **EUR** currency on the order row; virtual download + off-site return module.

## HTTP storefront — cart, attributes, stock, downloads

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Hosted download edge cases beyond maxdays / count | Partial | [`checkout_download_test.php`](../tests/http/checkout_download_test.php) covers Pending vs **Processing**, **`download_count`**, and **`download_maxdays`** |

Representative closed behavior: **`shopping_cart_test.php`** (buy_now, qty, removal, priced attribute prefixes); **`checkout_stock_test.php`** (block, decrement, allow-checkout warning); bogus download id.

## HTTP storefront — forms, mail, recorder, browse

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Every outbound storefront mail variant | Core paths only | Registration, password reset, contact, and money-order confirmation when capture is enabled — see [`http-tests.md`](http-tests.md) |

Representative closed behavior: login failure copy; advanced search filters and **`products_new.php`**; contact **`formid`**, captured mail, seeded and sequential **`ar_contact_us`** throttle; session UA mismatch after login.

## Installer — admin catalog, customer data, documents

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| **Admin order line editor add/remove products** | None | **Open** — blocked at [`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt) until CE ships stable order-editor endpoints for the pinned ref |
| Storefront **`create_account`** validation (required fields, duplicate email, password match, **`matc`**) | None | [`storefront_account_validation_test.php`](../tests/installer/storefront_account_validation_test.php) — omitted **`matc`** may **HTTP 500** on the pin when **`ENTRY_MATC_ERROR`** is undefined (see [`installer-tests.md`](installer-tests.md)) |

Representative closed behavior: admin catalog category/product copy/move; product price, description, and image upload on the storefront ([`admin_catalog_writes_test.php`](../tests/installer/admin_catalog_writes_test.php)); attributes, people, invoice qty **2**, hooks, compose mail.

## Browser, HTTPS, harness environment

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| **`SSL_SESSION_ID`** session binding | Not on plain HTTP | [`https-tests.md`](https-tests.md) |
| Every **`cm_*`** content module | Sample-shop smoke only | Not exhaustive — see [`http-tests.md`](http-tests.md) |
| Vendored install SQL vs catalog checkout | Manual diff | [`vendored_install_sql_test.php`](../tests/unit/invariants/vendored_install_sql_test.php) (byte match for `phoenix.sql` / `phoenix_data_sample.sql`; CRLF checkout fails) |
| Release certification scope | Ad hoc | [`release-certification.md`](release-certification.md) — pinned catalog + **`composer test:stack`** (+ optional browser); **PHP 8.4 / MariaDB 10.11** in CI only |

Representative closed behavior: carousel, offcanvas, checkout scratch, visual baseline, navbar search/currency, **`products_new`**, product attribute select — [`browser-tests.md`](browser-tests.md).

## HTTP storefront — account, GDPR, security (representative)

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Stale **`checkout_success.php`** redirect timing | HTTP e2e deferred | Unit guard in [`cm_cs_redirect_old_order_test.php`](../tests/unit/content/cm_cs_redirect_old_order_test.php) |

Other account/GDPR/security paths are covered in [`http-tests.md`](http-tests.md) (history, GDPR export/nuke, address book, **`Href::redirect`**, info pages).

## Integration and unit (supporting SQL / modules)

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Mock-db configuration paths | None | [`tests/unit/`](../tests/unit/) `#[Group('mockdb')]` — see [`TESTING.md`](../TESTING.md) |

Real MySQL configuration and content-module edge cases: [`tests/integration/`](../tests/integration/), [`cm_cs_redirect_old_order_test.php`](../tests/unit/content/cm_cs_redirect_old_order_test.php), [`cm_pi_review_stars_test.php`](../tests/unit/content/cm_pi_review_stars_test.php).

## Deferred / environmental

| Area | Notes |
|------|--------|
| Outgoing queue after storefront contact | Compose capture in [`admin_mail_test.php`](../tests/installer/admin_mail_test.php); queue list smoke on [`admin_outgoing_test.php`](../tests/installer/admin_outgoing_test.php) |
| Windows vs Linux path/PHP differences | Linux CI and Cloud are merge gate — [`TESTING.md`](../TESTING.md) |

## Recently closed (first-four + orphan slices)

| Item | Test / doc pointer |
|------|-------------------|
| Priced attribute order totals | [`checkout_priced_attribute_test.php`](../tests/http/checkout_priced_attribute_test.php) |
| Invoice / packingslip qty 2 | [`admin_order_documents_test.php`](../tests/installer/admin_order_documents_test.php) |
| Storefront hook fixture | [`storefront_hook_fixture_test.php`](../tests/installer/storefront_hook_fixture_test.php) |
| Login / search / free shipping / formid / router / UA | [`login_form_test.php`](../tests/http/login_form_test.php), [`catalog_browse_test.php`](../tests/http/catalog_browse_test.php), [`checkout_shipping_modules_test.php`](../tests/http/checkout_shipping_modules_test.php), [`request_security_test.php`](../tests/http/request_security_test.php) |
| Checkout money, currency, tax display (slice) | [`checkout_moneyorder_test.php`](../tests/http/checkout_moneyorder_test.php), [`checkout_currency_test.php`](../tests/http/checkout_currency_test.php), [`checkout_tax_display_test.php`](../tests/http/checkout_tax_display_test.php), [`http_orders_lookup.php`](../tests/support/http_orders_lookup.php) |
| Cart stock, priced attributes, download lifecycle (slice) | [`shopping_cart_test.php`](../tests/http/shopping_cart_test.php), [`checkout_stock_test.php`](../tests/http/checkout_stock_test.php), [`checkout_download_test.php`](../tests/http/checkout_download_test.php), [`http_admin_fixture.php`](../tests/support/http_admin_fixture.php) |
| Mail capture + action recorder (slice) | [`contact_us_test.php`](../tests/http/contact_us_test.php), [`create_account_test.php`](../tests/http/create_account_test.php), [`password_reset_test.php`](../tests/http/password_reset_test.php), [`http_mail_capture.php`](../tests/support/http_mail_capture.php) |
| Harness limits + vendored SQL pin (slice) | [`vendored_install_sql_test.php`](../tests/unit/invariants/vendored_install_sql_test.php), [`product_attribute_select.spec.ts`](../tests/browser/product_attribute_select.spec.ts), [`release-certification.md`](release-certification.md) |
| Installer catalog storefront + account validation (slice) | [`admin_catalog_writes_test.php`](../tests/installer/admin_catalog_writes_test.php), [`storefront_account_validation_test.php`](../tests/installer/storefront_account_validation_test.php) |

Run **`composer test:stack`** and **`composer test:installer`** on Cloud after **`bash fixtures/import-mysql-fixtures.sh`**, starting the HTTP server (**`bash scripts/http-server.sh`**) for stack tests and letting **`composer test:installer`** start its own server (see suite docs). Expect **118** installer tests after the consolidated branch.
