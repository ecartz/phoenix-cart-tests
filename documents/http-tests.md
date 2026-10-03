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
2. Write catalog configure and start the built-in server: **`bash scripts/http-server.sh`** (document root = **`PHOENIX_CART_ROOT`**, default port **8765**)
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
- Product review write for pears with temporary `ALLOW_ALL_REVIEWS` (`product_review_test`; [`http_review_fixture_sql.php`](../tests/support/http_review_fixture_sql.php))
- COD order detail on `account_history_info.php` plus non-numeric `order_id` redirect (`account_history_info_test`)
- Account password, newsletter, and global product notification POSTs with fixture restore in `tearDown` (`account_preferences_test`)
- Session currency EUR/USD symbols and English language fallback for unknown `language` (`session_locale_test`)
- GDPR intro page and logged-in `gdpr_data` JSON export with store name (`account_gdpr_test`)
- Address book insert and delete (`address_book_test`)
- Password forgotten → reset key from MySQL → new password login (`password_reset_test`; restores seed hash in `tearDown`)
- Contact form success message (`contact_us_test`)
- Search, specials, new products, testimonials, and manufacturer browse (`catalog_browse_test`)
- Logged-in checkout through COD and flat shipping to `checkout_success.php` (`checkout_cod_test`; fixture customer in [`fixtures/http/seed_customer.sql`](../fixtures/http/seed_customer.sql)); asserts stored `orders.payment_method` is **Cash on Delivery** and the latest order has Florida **`ot_tax`**
- Checkout order comment stored in `orders_status_history` (`checkout_comments_test`)
- Checkout with a secondary address on shipping/payment address steps (`checkout_alternate_address_test`)
- Stock block redirect when `STOCK_ALLOW_CHECKOUT` is false and product quantity is zero (`checkout_stock_test`)
- Item, zone, and table shipping modules plus flat geo-zone hide and free-shipping confirmation (`checkout_shipping_modules_test`; temporary config via [`http_checkout_fixture_sql.php`](../tests/support/http_checkout_fixture_sql.php))
- Mixed virtual + physical cart still shows shipping and COD (`checkout_mixed_cart_test`)
- Money-order virtual order download after status **Processing** (`checkout_download_test`; writes `download/http-test-download.zip` under the catalog root for the test)
- Same flow with Check/Money Order (`checkout_moneyorder_test`; confirmation shows fixture payee **Your Store** from `MODULE_PAYMENT_MONEYORDER_PAYTO`); asserts `payment_method` **Check/Money Order**
- Virtual download cart skips shipping, hides COD, completes with money order (`checkout_virtual_cod_test`; temporary attribute/download rows on product 3 via [`http_checkout_fixture_sql.php`](../tests/support/http_checkout_fixture_sql.php))
- COD payment zone excludes the fixture Florida address (`checkout_geo_zone_cod_test`; temporary geo zone + `MODULE_PAYMENT_COD_ZONE`)
- Logged-out checkout creates an account mid-flow then completes COD (`checkout_new_account_test`)
- Off-site confirmation form pipeline without PayPal/Stripe (`checkout_local_redirect_test`; copies [`fixtures/http/http_local_redirect.php`](../fixtures/http/http_local_redirect.php) and [`fixtures/http/http_local_redirect_return.php`](../fixtures/http/http_local_redirect_return.php) into the catalog `ext/` tree for the test, exercises good/bad tokens on `ext/modules/payment/http_local_redirect/return.php` and `checkout_process.php`, asserts `payment_method` **HTTP Local Redirect Fixture**)
- Info and slug pages (`info_page_test`); extra SQL in [`fixtures/http/`](../fixtures/http/)
- Redirect hardening for `Href::redirect` entrypoints (`href_redirect_test`)
- Request user-agent / IP mismatch → login redirect (`request_security_test`; uses `enable_session_security_checks.sql`)
- Timed homepage smoke (`homepage_timing_test`; budget via **`PHOENIX_HTTP_BUDGET_SECONDS`**, default 10)

Payment sandbox config smoke lives in the same directory but runs via **`composer test:payment-sandbox`** ([`payment-sandbox.md`](payment-sandbox.md)).

SSL session id checks run over Apache HTTPS, not plain HTTP — see [`https-tests.md`](https-tests.md).
