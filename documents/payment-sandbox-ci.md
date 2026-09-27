# Payment sandbox (wave 6 part 3)

Optional **Stripe SCA test mode** wiring for CI and local runs. Secrets stay in environment or GitHub Actions secrets — never in git.

## Enable

```bash
export PHOENIX_PAYMENT_SANDBOX_ENABLED=1
export PHOENIX_STRIPE_SCA_TEST_PUBLISHABLE_KEY=pk_test_...
export PHOENIX_STRIPE_SCA_TEST_SECRET_KEY=sk_test_...
```

Import fixtures, apply config, run tests:

```bash
bash fixtures/import-mysql-fixtures.sh
php scripts/apply-payment-sandbox-config.php
export PHOENIX_HTTP_ENABLED=1
export PHOENIX_HTTP_BASE_URL=http://127.0.0.1:8765
composer test:payment-sandbox
```

Or one step after import when sandbox is enabled:

```bash
export PHOENIX_PAYMENT_SANDBOX_ENABLED=1
# ... keys ...
bash fixtures/import-mysql-fixtures.sh   # runs apply when flag is set
```

## GitHub Actions secrets

Add repository secrets (names must match env vars):

| Secret | Purpose |
|--------|---------|
| `PHOENIX_STRIPE_SCA_TEST_PUBLISHABLE_KEY` | Stripe test publishable key |
| `PHOENIX_STRIPE_SCA_TEST_SECRET_KEY` | Stripe test secret key |

Run manually: [`.github/workflows/payment-sandbox.yml`](../.github/workflows/payment-sandbox.yml) (`workflow_dispatch`).

Default **`composer cloud-test`** and **`phpunit-mysql.yml`** do **not** run payment sandbox tests.

## What is tested

[`payment_sandbox_stripe_config_test.php`](../tests/Http/payment_sandbox_stripe_config_test.php) (`#[Group('payment_sandbox')]`) asserts:

- Test keys are written to the `configuration` table (publishable key prefix `pk_test_`).
- Homepage HTML does not echo the secret key.

Full checkout / Stripe.js / iframe flows remain out of scope.
