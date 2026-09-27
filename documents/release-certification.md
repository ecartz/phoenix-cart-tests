# Release certification (wave 6 part 4)

Certify that **this test repo** at a **git tag** passes the full harness against a **matching CE-PhoenixCart release tag**.

## Tag pairing

| Repo | Tag rule |
|------|----------|
| [CE-PhoenixCart/PhoenixCart](https://github.com/CE-PhoenixCart/PhoenixCart) | **`master`** (current release) or a semver tag for certified releases |
| `phoenix-cart-tests` | **Same ref** as [`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt) when publishing a certified test release; default pin is **`master`** |

## Catalog pin

[`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt) records the CE tag vendored fixtures were copied from. Bump it when refreshing [`fixtures/phoenix.sql`](../fixtures/phoenix.sql) / [`fixtures/phoenix_data_sample.sql`](../fixtures/phoenix_data_sample.sql) (see [`fixtures/README.md`](../fixtures/README.md)).

## Run locally or on Cloud

Requires MariaDB/MySQL, PHP, Composer, and (for Http/browser) the built-in server — same as [`composer cloud-test`](../composer.json).

```bash
export PHOENIX_DB_HOST=127.0.0.1
export PHOENIX_DB_USER=phoenix
export PHOENIX_DB_PASSWORD=phoenix
export PHOENIX_DB_NAME=phoenix_test
# Optional: override catalog tag
# export PHOENIX_CATALOG_TAG=1.1.0.8
composer release-certify
```

Optional Playwright after PHPUnit:

```bash
export PHOENIX_BROWSER_ENABLED=1
composer release-certify
```

## Publishing a certified test release

1. Refresh fixtures from the CE tag; update `fixtures/catalog_pin.txt`.
2. Run `composer release-certify` on Linux (Cloud) so Playwright snapshots and Integration match CI.
3. Tag this repo with the **same** CE release tag and push the tag.

GitHub Actions [`.github/workflows/release-certification.yml`](../.github/workflows/release-certification.yml) re-runs certification when a tag is pushed to this repository.
