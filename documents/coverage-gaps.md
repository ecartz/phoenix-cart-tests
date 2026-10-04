# Coverage gaps (first four)

Tracked items from the coverage-first-four plan. Each row maps to PHPUnit coverage in this repo (not PhoenixCart core).

| # | Gap | Coverage |
|---|-----|----------|
| 1 | Checkout with priced product attribute (+$1.25) and quantity 2; assert new `orders_id` and exact `orders_total` rows | [`checkout_priced_attribute_test.php`](../tests/http/checkout_priced_attribute_test.php); [`http_orders_lookup.php`](../tests/support/http_orders_lookup.php) (`orders_total_value_for_order`, `orders_products_quantity_for_order`) |
| 2 | Admin invoice/packingslip for COD order with line quantity 2 | [`admin_order_documents_test.php`](../tests/installer/admin_order_documents_test.php) |
| 3 | Storefront hook fixture toggled on/off via configuration; admin hooks list | [`fixtures/http/http_storefront_hook_marker.php`](../fixtures/http/http_storefront_hook_marker.php); [`storefront_hook_fixture_test.php`](../tests/installer/storefront_hook_fixture_test.php) |
| 4 | Tighter HTTP assertions (login formid rotation, browse/manufacturers, free shipping `ot_shipping` 0, contact mail capture, per-request router env sync, session UA mismatch) | [`login_form_test.php`](../tests/http/login_form_test.php), [`catalog_browse_test.php`](../tests/http/catalog_browse_test.php), [`checkout_shipping_modules_test.php`](../tests/http/checkout_shipping_modules_test.php), [`contact_us_test.php`](../tests/http/contact_us_test.php), [`php-built-in-router.php`](../scripts/php-built-in-router.php), [`request_security_test.php`](../tests/http/request_security_test.php) |

Run **`composer test:stack`** and **`composer test:installer`** on Cloud after importing fixtures and starting the HTTP/installer servers (see suite docs).
