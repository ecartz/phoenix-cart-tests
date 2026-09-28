# Deferred coverage

Areas **not yet covered** (or only partially) by this harness. This is not a log of completed work — see [`documents/README.md`](documents/README.md) for what each suite runs today.

PHPUnit tests that call `markTestSkipped()` when an env flag or secret is missing (MySQL, HTTP, HTTPS, payment sandbox) are described in the matching document under `documents/`. They are implemented; they just do not run in every environment.

| Area | Why deferred | Likely suite |
|------|----------------|--------------|
| Payment provider checkout UI (Stripe.js, PayPal iframes, etc.) | Provider iframes and live card/wallet capture; catalog checkout with COD is covered by [`checkout_cod_test.php`](tests/http/checkout_cod_test.php) | out of scope |
| Payment **modules** as behavior (not config smoke) | Same as above; [`payment_sandbox_stripe_config_test.php`](tests/http/payment_sandbox_stripe_config_test.php) only checks keys in `configuration` | out of scope |
| Admin UI | Admin tree and back-office HTTP beyond installer login smoke | out of scope |

When adding coverage, remove or narrow the row here and add the test under the appropriate `tests/` directory.
