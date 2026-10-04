# Coverage gaps (behavior harness)

This page tracks **known gaps** between CE Phoenix Cart behavior and what the harness asserts today. It is not PHPUnit line coverage. When a gap closes, narrow or remove the row and point to the test in [`http-tests.md`](http-tests.md), [`installer-tests.md`](installer-tests.md), or [`browser-tests.md`](browser-tests.md).

Hosted PayPal, Stripe, and 2Checkout checkout remain out of scope — see [`SKIPPED.md`](../SKIPPED.md).

Merged HTTP/installer coverage from [`cursor/remaining-coverage-gaps-a33a`](https://github.com/ecartz/phoenix-cart-tests) supersedes the old **First tests worth adding** items 1–4 (priced-attribute checkout, invoice qty 2, storefront hook fixture, tightened login/search/shipping/contact/security). See the **Recently closed** section below.

## HTTP storefront — checkout, money, and tax

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| COD checkout stored totals | Confirmation HTML only | [`checkout_cod_test.php`](../tests/http/checkout_cod_test.php) + [`http_orders_lookup.php`](../tests/support/http_orders_lookup.php) (`payment_method`, Florida **`ot_tax`**) |
| Check/Money Order payee, stored method, totals, confirmation mail | Confirmation copy only | [`checkout_moneyorder_test.php`](../tests/http/checkout_moneyorder_test.php) |
| Session currency EUR on checkout | None | [`checkout_currency_test.php`](../tests/http/checkout_currency_test.php) |
| Tax-inclusive display + taxable shipping | None | [`checkout_tax_display_test.php`](../tests/http/checkout_tax_display_test.php) |
| Per-item shipping stored total | Module smoke only | [`checkout_shipping_modules_test.php`](../tests/http/checkout_shipping_modules_test.php) (**`ot_shipping`** **2.50**) |
| Priced attribute (+$1.25) qty 2 through COD | Cart attribute smoke only | [`checkout_priced_attribute_test.php`](../tests/http/checkout_priced_attribute_test.php): exact **`ot_subtotal` / `ot_shipping` / `ot_tax` / `ot_total`**, line qty **2** |
| Free shipping stored total | Confirmation HTML only | [`checkout_shipping_modules_test.php`](../tests/http/checkout_shipping_modules_test.php) asserts **`ot_shipping`** **0** |
| Virtual download + money order | Partial paths | [`checkout_download_test.php`](../tests/http/checkout_download_test.php), [`checkout_virtual_cod_test.php`](../tests/http/checkout_virtual_cod_test.php) |
| Shipping module matrix (item/zone/table/geo) | Flat rate only | [`checkout_shipping_modules_test.php`](../tests/http/checkout_shipping_modules_test.php) via [`http_checkout_fixture_sql.php`](../tests/support/http_checkout_fixture_sql.php) |
| Off-site return module (non-hosted) | None | [`checkout_local_redirect_test.php`](../tests/http/checkout_local_redirect_test.php) |
| Hosted card/wallet checkout | Not attempted | Out of scope — [`SKIPPED.md`](../SKIPPED.md) |

## HTTP storefront — cart, attributes, stock, downloads

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| `buy_now`, qty update, line removal, priced attribute **+/-/ %** | Minimal | [`shopping_cart_test.php`](../tests/http/shopping_cart_test.php) |
| Non-download attribute line in cart | None | [`shopping_cart_test.php`](../tests/http/shopping_cart_test.php) |
| Stock block, decrement, allow-zero warning | Block only | [`checkout_stock_test.php`](../tests/http/checkout_stock_test.php) |
| Virtual download pending / maxdays / maxcount / admin status | Processing only | [`checkout_download_test.php`](../tests/http/checkout_download_test.php) |
| Bogus download URL | None | [`download_bogus_id_test.php`](../tests/http/download_bogus_id_test.php) |

## HTTP storefront — forms, mail, recorder, browse

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Login failure copy + formid on retry | Loose substring | [`login_form_test.php`](../tests/http/login_form_test.php) |
| Advanced search filters + testimonials modules | Product names only | [`catalog_browse_test.php`](../tests/http/catalog_browse_test.php): category/price/description filters, **`cm-asr-title`**, **`cm-t-title`**, **`cm-t-list`**, **`manufacturers.php`** |
| Contact us mail capture + bad formid | Success message only | [`contact_us_test.php`](../tests/http/contact_us_test.php) + [`http_mail_capture.php`](../tests/support/http_mail_capture.php) |
| Contact us action recorder throttle | None | [`contact_us_test.php`](../tests/http/contact_us_test.php) + [`http_action_recorder_fixture_sql.php`](../tests/support/http_action_recorder_fixture_sql.php) |
| `products_new.php` listing | Bundled in browse test only | [`products_new_test.php`](../tests/http/products_new_test.php); Playwright [`products_new.spec.ts`](../tests/browser/products_new.spec.ts) |
| Session UA mismatch after login | Anonymous only | [`request_security_test.php`](../tests/http/request_security_test.php); [`php-built-in-router.php`](../scripts/php-built-in-router.php) syncs **`HTTP_*`** per request |
| Password reset mail + forgotten edges | Partial | [`password_reset_test.php`](../tests/http/password_reset_test.php) (captured reset mail), [`password_forgotten_unknown_test.php`](../tests/http/password_forgotten_unknown_test.php) |
| Registration welcome mail | None | [`create_account_test.php`](../tests/http/create_account_test.php) |

## Installer — admin catalog, customer data, documents

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Catalog category/product CRUD, copy, move | Read-only admin smoke | [`admin_catalog_writes_test.php`](../tests/installer/admin_catalog_writes_test.php) (storefront price/description + optional **`products_image`** upload), [`admin_forms_test.php`](../tests/installer/admin_forms_test.php) |
| Product attributes on Pears | None | [`admin_attributes_test.php`](../tests/installer/admin_attributes_test.php) |
| People: admins, customers, orders delete | Partial | [`admin_people_test.php`](../tests/installer/admin_people_test.php) |
| Invoice/packingslip qty **2** | Single qty smoke | [`admin_order_documents_test.php`](../tests/installer/admin_order_documents_test.php) |
| Storefront hook fixture toggle + admin hooks list | None | [`storefront_hook_fixture_test.php`](../tests/installer/storefront_hook_fixture_test.php) |
| Compose mail / order notify capture | None | [`admin_mail_test.php`](../tests/installer/admin_mail_test.php) |
| Storefront **`create_account`** validation + address zones | HTTP welcome mail only | [`storefront_account_validation_test.php`](../tests/installer/storefront_account_validation_test.php) — omitted **`matc`** may **HTTP 500** on the pin when **`ENTRY_MATC_ERROR`** is undefined (see [`installer-tests.md`](installer-tests.md)) |
| **Admin order line editor add/remove products** | None | **Blocked** — catalog pin **`master`** ([`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt)): `admin/orders.php?action=edit` renders the Products tab as read-only line HTML (no line-item form); the only POST on that screen is **`action=update_order`** (`status`, `comments`, `notify`). There is no `update_products` (or add/remove line) handler under `admin/includes/actions/orders/`. Harness cannot POST invented line edits or assert invoice **totals** after admin line changes until CE ships that write. Storefront qty **2** on invoice/packingslip remains [`admin_order_documents_test.php`](../tests/installer/admin_order_documents_test.php). |

## Browser, HTTPS, harness environment

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Carousel / offcanvas / checkout scratch | None | Playwright specs — [`browser-tests.md`](browser-tests.md) |
| Navbar search + currency | None | [`search_form.spec.ts`](../tests/browser/search_form.spec.ts), [`currency_dropdown.spec.ts`](../tests/browser/currency_dropdown.spec.ts) |
| Visual regression baseline | None | [`homepage_visual.spec.ts`](../tests/browser/homepage_visual.spec.ts) |
| Product attribute select (Lemons **Box Size**) | None | [`product_attribute_select.spec.ts`](../tests/browser/product_attribute_select.spec.ts) |
| **`SSL_SESSION_ID`** session binding | Not on plain HTTP | [`https-tests.md`](https-tests.md) |
| Every **`cm_*`** content module | Sample-shop smoke only | Not exhaustive — see [`http-tests.md`](http-tests.md) |
| Vendored install SQL vs catalog pin checkout | Manual diff | [`vendored_install_sql_test.php`](../tests/unit/invariants/vendored_install_sql_test.php) (byte match on `phoenix.sql` + `phoenix_data_sample.sql`) |
| Release certification scope | Ad hoc | [`release-certification.md`](release-certification.md) — pinned catalog + stack + optional browser + **`composer test:installer`** + optional HTTPS; **PHP 8.4 / MariaDB 10.11** in release CI; **MySQL 8** for unit + integration in **`mysql8-unit-integration`** |

## HTTP storefront — account, GDPR, security (representative)

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Account history / order detail | List only | [`account_history_test.php`](../tests/http/account_history_test.php), [`account_history_info_test.php`](../tests/http/account_history_info_test.php) |
| GDPR export / nuke | None | [`account_gdpr_test.php`](../tests/http/account_gdpr_test.php), [`account_gdpr_nuke_test.php`](../tests/http/account_gdpr_nuke_test.php) |
| Address book / preferences | Partial | [`address_book_test.php`](../tests/http/address_book_test.php), [`account_preferences_test.php`](../tests/http/account_preferences_test.php) |
| `Href::redirect` hardening | None | [`href_redirect_test.php`](../tests/http/href_redirect_test.php) |
| Info / slug pages | None | [`info_page_test.php`](../tests/http/info_page_test.php) |
| Stale **`checkout_success.php`** redirect timing | HTTP e2e deferred | Unit guard in [`cm_cs_redirect_old_order_test.php`](../tests/unit/content/cm_cs_redirect_old_order_test.php) |

## Integration and unit (supporting SQL / modules)

| Gap | Prior state | Harness today |
|-----|-------------|---------------|
| Real MySQL configuration load | Mock only | [`tests/integration/`](../tests/integration/) with fixture DB |
| Content module edge cases | None | [`cm_cs_redirect_old_order_test.php`](../tests/unit/content/cm_cs_redirect_old_order_test.php), [`cm_pi_review_stars_test.php`](../tests/unit/content/cm_pi_review_stars_test.php) |
| Mock-db configuration paths | None | [`tests/unit/`](../tests/unit/) `#[Group('mockdb')]` — see [`TESTING.md`](../TESTING.md) |

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
