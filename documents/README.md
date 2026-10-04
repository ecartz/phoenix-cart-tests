# Test harness documentation

These pages describe how automated tests in this repository are organized and how to run each layer. They match the directories under `tests/` and the Composer scripts in [`composer.json`](../composer.json).

For quick local commands, see the root [`README.md`](../README.md). For how CE Phoenix Cart CI chooses a git ref for this repo, see [`TESTING.md`](../TESTING.md).

This harness exercises **behavior** on the fixture sample shop and on disposable installer installs: storefront flows over HTTP, representative admin actions, a small Playwright set, and unit/integration checks for shared catalog code. It is not line coverage of the entire Phoenix tree. Hosted card and wallet checkouts (**PayPal**, **Stripe**, **2Checkout**) are out of scope — see [`SKIPPED.md`](../SKIPPED.md).

## Composer suites

| Script | What runs |
|--------|-----------|
| **`composer test`** | Unit tests only (`tests/unit/`) |
| **`composer test:mysql`** | Integration tests (`tests/integration/`) |
| **`composer test:http`** | HTTP acceptance (`tests/http/`) |
| **`composer test:https`** | Apache HTTPS (`tests/https/`) |
| **`composer test:browser`** | Playwright (`tests/browser/`) |
| **`composer test:stack`** | Unit + integration + HTTP (Cloud, release PHPUnit, default GitHub Actions full stack) |
| **`composer test:all`** | Stack plus HTTPS; HTTPS cases skip when Apache is not configured |
| **`composer test:payment-sandbox`** | Stripe config smoke (`#[Group('payment_sandbox')]`) |
| **`composer test:installer`** | Web installer wizard (`tests/installer/`) |
| **`composer release-certify`** | Pinned catalog checkout + stack (+ optional browser) |
| **`composer cloud-test`** | Same phases as CI full stack on Cursor Cloud (see [`TESTING.md`](../TESTING.md) and optional local [`AGENTS.md`](../AGENTS.md)) |

## Pages

| Document | Topic |
|----------|--------|
| [`unit-tests.md`](unit-tests.md) | Pure PHPUnit against catalog classes without booting the shop |
| [`integration-tests.md`](integration-tests.md) | Real MySQL/MariaDB and fixture SQL |
| [`http-tests.md`](http-tests.md) | Symfony HttpClient against `php -S` and generated `configure.php` |
| [`browser-tests.md`](browser-tests.md) | Playwright against the same shop URL as HTTP tests |
| [`https-tests.md`](https-tests.md) | Apache TLS and `SSL_SESSION_ID` session checks |
| [`payment-sandbox.md`](payment-sandbox.md) | Optional Stripe SCA test keys in CI |
| [`installer-tests.md`](installer-tests.md) | Optional web installer on disposable catalog + DB |
| [`release-certification.md`](release-certification.md) | Tag-paired certification runs |
| [`coverage-gaps.md`](coverage-gaps.md) | Tracked HTTP/installer gap closures |

Shared support code lives under [`tests/support/`](../tests/support/). Hosted payment exclusions are listed in [`SKIPPED.md`](../SKIPPED.md).
