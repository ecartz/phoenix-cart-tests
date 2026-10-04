# Coverage gaps (behavior harness)

This page tracks **known gaps** between CE Phoenix Cart behavior and what the harness asserts today. It is not PHPUnit line coverage. When a gap closes, narrow or remove the row and point to the test in [`http-tests.md`](http-tests.md) or the matching suite doc.

Hosted PayPal, Stripe, and 2Checkout checkout remain out of scope — see [`SKIPPED.md`](../SKIPPED.md).

## HTTP storefront — checkout and orders

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Priced product attribute through COD | Cart attribute smoke only (`shopping_cart_test`); COD path did not assert stored line or `orders_total` rows | [`checkout_priced_attribute_test.php`](../tests/http/checkout_priced_attribute_test.php): Pears qty **2** with **+$1.25** attribute, before/after `orders_id`, confirmation HTML, `orders_products`, `orders_products_attributes`, and `ot_subtotal` / `ot_shipping` / `ot_tax` / `ot_total` via [`http_orders_lookup.php`](../tests/support/http_orders_lookup.php) |
| Free shipping order total | Confirmation HTML only for zero shipping | [`checkout_shipping_modules_test.php`](../tests/http/checkout_shipping_modules_test.php) completes checkout and asserts stored **`ot_shipping`** is **0** |
| Order row helpers | `payment_method` and `ot_tax` only | `http_orders_lookup::ot_row_for_order()`, `orders_products_line_for_order()`, `orders_products_attribute_rows_for_order()` |

## HTTP storefront — forms, browse, session security

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Login failure copy | Loose “password” substring match | [`login_form_test.php`](../tests/http/login_form_test.php): explicit **No match for E-mail Address and/or Password** plus successful fixture login reaches `account.php` |
| Advanced search / browse modules | Keywords and product names only | [`catalog_browse_test.php`](../tests/http/catalog_browse_test.php): `cm-asr-title`, product listing markup, `cm-t-title`, `cm-t-list` |
| Contact us | Success message only | [`contact_us_test.php`](../tests/http/contact_us_test.php): invalid **`formid`**, happy path asserts captured **`mail()`** via [`http_mail_capture.php`](../tests/support/http_mail_capture.php) |
| Session UA check after auth | Anonymous session only | [`request_security_test.php`](../tests/http/request_security_test.php): fixture login then UA mismatch on `account.php`; [`php-built-in-router.php`](../scripts/php-built-in-router.php) syncs `HTTP_*` / `REMOTE_ADDR` into `getenv()` on **each** request |

## Still open (not in HTTP sections 1 / 4)

| Area | Notes |
|------|--------|
| Browser carousel / visual regression | Playwright subset only — see [`browser-tests.md`](browser-tests.md) |
| Every `cm_*` content module | Sample-shop smoke, not exhaustive module matrix |
| Admin catalog (non-installer) | Installer admin writes only — see [`installer-tests.md`](installer-tests.md) |
| HTTPS `SSL_SESSION_ID` | [`https-tests.md`](https-tests.md), not plain HTTP |
