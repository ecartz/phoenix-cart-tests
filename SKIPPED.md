# Deferred coverage

Areas **not yet covered** (or only partially) by this harness. This is not a log of completed work — see [`documents/README.md`](documents/README.md) for what each suite runs today.

PHPUnit tests that call `markTestSkipped()` when an env flag or secret is missing (MySQL, HTTP, HTTPS, payment sandbox) are described in the matching document under `documents/`. They are implemented; they just do not run in every environment.

| Area | Why deferred | Likely suite |
|------|----------------|--------------|
| Payment provider checkout UI (Stripe.js, PayPal iframes, etc.) | Provider iframes and live card/wallet capture; in-shop checkout uses COD ([`checkout_cod_test.php`](tests/http/checkout_cod_test.php)), Check/Money Order ([`checkout_moneyorder_test.php`](tests/http/checkout_moneyorder_test.php)), virtual/zone/new-account edges ([`checkout_virtual_cod_test.php`](tests/http/checkout_virtual_cod_test.php), [`checkout_geo_zone_cod_test.php`](tests/http/checkout_geo_zone_cod_test.php), [`checkout_new_account_test.php`](tests/http/checkout_new_account_test.php)), stored `orders.payment_method` ([`http_orders_lookup.php`](tests/support/http_orders_lookup.php)), and the harness [`http_local_redirect`](fixtures/http/http_local_redirect.php) module ([`checkout_local_redirect_test.php`](tests/http/checkout_local_redirect_test.php)) including the copied `ext/` return hop—not hosted card or wallet UI | out of scope |
| Payment **modules** as behavior (not config smoke) | Stripe/PayPal hosted flows and catalog `ext/modules/payment/paypal/` / `stripe_sca/` provider scripts stay out of scope; [`payment_sandbox_stripe_config_test.php`](tests/http/payment_sandbox_stripe_config_test.php) only checks keys in `configuration`; the local redirect fixture exercises `form_action_url`, `ext/` return, `before_process`, stored `payment_method`, rejected tokens, virtual carts, COD geo zone, and account creation during checkout without calling provider APIs | out of scope |

Installer admin **compose mail**, **newsletter send**, and **order notify** are covered by [`admin_mail_test.php`](tests/installer/admin_mail_test.php) using captured **`mail()`** on Linux (see [`documents/installer-tests.md`](documents/installer-tests.md)); they are not deferred here.

When adding coverage, remove or narrow the row here and add the test under the appropriate `tests/` directory.
