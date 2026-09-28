# Release certification

Release certification checks that **this test repository** passes the harness against a defined **CE-PhoenixCart** git ref, using the same fixture SQL and (optionally) browser snapshots committed here.

## Catalog pin

| Repo | Ref |
|------|-----|
| [CE-PhoenixCart/PhoenixCart](https://github.com/CE-PhoenixCart/PhoenixCart) | [`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt) (default **`master`**, current release branch) |
| `phoenix-cart-tests` | Same ref when publishing a certified test release; override with **`PHOENIX_CATALOG_TAG`** |

[`fixtures/catalog_pin.txt`](../fixtures/catalog_pin.txt) also documents which CE ref the vendored SQL was copied from. Refresh [`fixtures/phoenix.sql`](../fixtures/phoenix.sql) and [`fixtures/phoenix_data_sample.sql`](../fixtures/phoenix_data_sample.sql) when upstream install files change (see [`fixtures/README.md`](../fixtures/README.md)).

## What runs

[`scripts/release-certification.sh`](../scripts/release-certification.sh) checks out the catalog at the pin, imports fixtures, starts the HTTP server when enabled, then:

1. **`composer test:stack`** (unit + integration + HTTP)
2. Optional **`composer test:browser`** when **`PHOENIX_BROWSER_ENABLED=1`**

Entry point: **`composer release-certify`**. Requires MariaDB/MySQL, PHP, Composer, and the same env vars as local HTTP tests.

```bash
export PHOENIX_DB_HOST=127.0.0.1
export PHOENIX_DB_USER=phoenix
export PHOENIX_DB_PASSWORD=phoenix
export PHOENIX_DB_NAME=phoenix_test
# Optional: export PHOENIX_CATALOG_TAG=1.1.0.8
composer release-certify
```

Optional Playwright:

```bash
export PHOENIX_BROWSER_ENABLED=1
composer release-certify
```

HTTPS is not part of release certification today; run [`https-tests.md`](https-tests.md) separately or use **`composer cloud-test`** / CI full stack for Apache coverage.

## Publishing a certified test release

1. Refresh fixtures from the CE ref; update **`fixtures/catalog_pin.txt`** if the pin changes.
2. Run **`composer release-certify`** on Linux so Playwright snapshots and integration match CI.
3. Tag this repo with the same CE release tag (when certifying a semver release) and push the tag.

GitHub Actions [`.github/workflows/release-certification.yml`](../.github/workflows/release-certification.yml) re-runs certification on **tag push** (and manual dispatch).
