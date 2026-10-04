# HTTP acceptance tests

HTTP tests send real requests to a **running** CE Phoenix Cart shop backed by the fixture database. PHPUnit uses **Symfony HttpClient** with a cookie jar so sessions behave like a browser for server-rendered flows.

## Location and command

| Item | Value |
|------|--------|
| Directory | [`tests/http/`](../tests/http/) |
| Group | **`#[Group('http')]`** |
| PHPUnit testsuite | `http` |
| Run | **`composer test:http`** when the shop is up |

HTTP tests are included in **`composer test:stack`** and **`composer test:all`** when env flags are set; they are **not** in the default **`composer test`** (unit only).

## Prerequisites

1. Import fixtures: **`bash fixtures/import-mysql-fixtures.sh`**
2. Write catalog configure and start the built-in server: **`bash scripts/http-server.sh`** (document root = **`PHOENIX_CART_ROOT`**, default port **8765**). The server sets **`sendmail_path`** to [`scripts/capture-installer-mail.php`](../scripts/capture-installer-mail.php) so storefront **`mail()`** is captured under **`working/http-mail/`** (same mechanism as the installer server; see [`http_mail_capture.php`](../tests/support/http_mail_capture.php) for future assertions).
3. Set skip gate and origin:

```bash
export PHOENIX_HTTP_ENABLED=1
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
export PHOENIX_MYSQL_ENABLED=1
export PHOENIX_CART_ROOT=./PhoenixCart
composer test:http
```

[`tests/support/http_bootstrap.php`](../tests/support/http_bootstrap.php) and [`http_test_case`](../tests/support/http_test_case.php) generate **`includes/local/configure.php`** under the catalog from `PHOENIX_HTTP_BASE_URL` and `PHOENIX_DB_*`. That file is not committed (see [`fixtures/http/README.md`](../fixtures/http/README.md)).

Unset **`PHOENIX_HTTP_ENABLED`** and the Http suite skips (same pattern as **`PHOENIX_MYSQL_ENABLED`** for integration).

## Coverage

Examples in this suite:

- Homepage and category/product GET (`index_smoke_test`, `product_info_test`, `category_listing_test`)
- Cart session via `buy_now`, quantity updates, line removal, and a non-download attribute line (`shopping_cart_test`)
- Logged-in account pages, profile edit, and logoff redirect (`account_pages_test`)
- Product review write for pears with temporary `ALLOW_ALL_REVIEWS`, then review text on `product_info.php` (`product_review_test`; [`http_review_fixture_sql.php`](../tests/support/http_review_fixture_sql.php))
- Order history list, foreign `order_id` redirect, and `checkout_success.php` thank-you module (`account_history_test`; other customer order via [`http_order_fixture_sql.php`](../tests/support/http_order_fixture_sql.php))
- Standalone `create_account.php` registration and login (`create_account_test.php`; tearDown deletes throwaway rows)
- Logged-in testimonial write (`testimonial_write_test.php`; [`http_testimonial_fixture_sql.php`](../tests/support/http_testimonial_fixture_sql.php))
- Wrong login password with formid rotation (`login_form_test`), wrong `password_current` (`account_password_wrong_test`), password forgotten for unknown email (`password_forgotten_unknown_test`)
- Per-product notification checkbox (`account_preferences_test`); address edit, primary switch, and primary delete refusal (`address_book_test`)
- `<title>` and canonical links (`header_tags_test.php`); oranges regular vs special price on product 1 (`product_info_test`)
- `advanced_search.php` form plus existing browse/search flows (`catalog_browse_test`)
- COD confirmation subtotal/shipping/total lines (`checkout_cod_test`)
- `cm_cs_redirect_old_order` unit guard when redirect minutes are disabled ([`cm_cs_redirect_old_order_test.php`](../tests/unit/content/cm_cs_redirect_old_order_test.php)); `cm_pi_review_stars` unit ([`cm_pi_review_stars_test.php`](../tests/unit/content/cm_pi_review_stars_test.php))
- GDPR JSON export includes orders and reviews after COD + review (`account_gdpr_test`); throwaway account nuke (`account_gdpr_nuke_test.php`)
- Bogus `download.php` order/id (`download_bogus_id_test.php`)
- COD checkout then `account_history_info.php` shows order line (Pears, Flat Rate, `$4.99`); stored `payment_method` **Cash on Delivery** via [`http_orders_lookup`](../tests/support/http_orders_lookup.php); non-numeric `order_id` redirects to `account_history.php` (`account_history_info_test`)
- Account password, newsletter, and global product notification POSTs with fixture restore in `tearDown` (`account_preferences_test`)
- `?currency=` on `product_info.php` sets **Selected Currency** (EUR vs USD) and converted pears price (`data-product-price="4.25"` vs displayed `$4.99`); English `language=en` and unknown `language` fallback (`session_locale_test`)
- GDPR intro page and logged-in `gdpr_data` JSON export with store name (`account_gdpr_test`)
- Address book insert and delete (`address_book_test`)
- Password forgotten → reset key from MySQL → new password login (`password_reset_test`; restores seed hash in `tearDown`)
- Contact form success message and captured shopowner mail (`contact_us_test`; [`http_mail_capture.php`](../tests/support/http_mail_capture.php))
- Search, specials, new products, testimonials, manufacturer browse, and `manufacturers.php` index (`catalog_browse_test`)
- Logged-in checkout through COD and flat shipping to `checkout_success.php` (`checkout_cod_test`; fixture customer in [`fixtures/http/seed_customer.sql`](../fixtures/http/seed_customer.sql)); asserts stored `orders.payment_method` is **Cash on Delivery** and the latest order has Florida **`ot_tax`**
- Checkout order comment stored in `orders_status_history` (`checkout_comments_test`)
- Checkout with a secondary address on shipping/payment address steps (`checkout_alternate_address_test`)
- Stock block redirect when `STOCK_ALLOW_CHECKOUT` is false and product quantity is zero (`checkout_stock_test`)
- Item, zone, and table shipping modules plus flat geo-zone hide, free-shipping confirmation, and stored **`ot_shipping`** `0` (`checkout_shipping_modules_test`; temporary config via [`http_checkout_fixture_sql.php`](../tests/support/http_checkout_fixture_sql.php))
- Priced attribute (+`$1.25`) on Pears with quantity 2 and exact `orders_total` rows (`checkout_priced_attribute_test`; [`http_orders_lookup.php`](../tests/support/http_orders_lookup.php))
- Mixed virtual + physical cart still shows shipping and COD (`checkout_mixed_cart_test`)
- Money-order virtual order download after status **Processing** (`checkout_download_test`; writes `download/http-test-download.zip` under the catalog root for the test)
- Same flow with Check/Money Order (`checkout_moneyorder_test`; confirmation shows fixture payee **Your Store** from `MODULE_PAYMENT_MONEYORDER_PAYTO`); asserts `payment_method` **Check/Money Order**
- Virtual download cart skips shipping, hides COD, completes with money order (`checkout_virtual_cod_test`; temporary attribute/download rows on product 3 via [`http_checkout_fixture_sql.php`](../tests/support/http_checkout_fixture_sql.php))
- COD payment zone excludes the fixture Florida address (`checkout_geo_zone_cod_test`; temporary geo zone + `MODULE_PAYMENT_COD_ZONE`)
- Logged-out checkout creates an account mid-flow then completes COD (`checkout_new_account_test`)
- Off-site confirmation form pipeline without PayPal/Stripe (`checkout_local_redirect_test`; copies [`fixtures/http/http_local_redirect.php`](../fixtures/http/http_local_redirect.php) and [`fixtures/http/http_local_redirect_return.php`](../fixtures/http/http_local_redirect_return.php) into the catalog `ext/` tree for the test, exercises good/bad tokens on `ext/modules/payment/http_local_redirect/return.php` and `checkout_process.php`, asserts `payment_method` **HTTP Local Redirect Fixture**)
- Info and slug pages (`info_page_test`): `info.php?pages_id=` plus `privacy.php`, `conditions.php`, and `shipping.php`; extra SQL in [`fixtures/http/`](../fixtures/http/)
- Redirect hardening for `Href::redirect` entrypoints (`href_redirect_test`)
- Request user-agent / IP mismatch → login redirect (`request_security_test`; uses `enable_session_security_checks.sql`; built-in router syncs `HTTP_*` per request via [`php-built-in-router.php`](../scripts/php-built-in-router.php))
- Timed homepage smoke (`homepage_timing_test`; budget via **`PHOENIX_HTTP_BUDGET_SECONDS`**, default 10)

Payment sandbox config smoke lives in the same directory but runs via **`composer test:payment-sandbox`** ([`payment-sandbox.md`](payment-sandbox.md)).

SSL session id checks run over Apache HTTPS, not plain HTTP — see [`https-tests.md`](https-tests.md).
