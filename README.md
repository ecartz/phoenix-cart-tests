# CE Phoenix Cart tests

PHPUnit harness for [CE Phoenix Cart](https://github.com/CE-PhoenixCart/PhoenixCart). Tests live here so the shop fork stays close to upstream.

## Requirements

- PHP 7.3+ with `mbstring` and `xml`
- Composer
- Phoenix Cart catalog tree (sibling `../PhoenixCart` or shallow clone in `./PhoenixCart`)

## Run locally

```bash
composer install
composer test
# or: vendor/bin/phpunit
```

Override catalog location:

```bash
export PHOENIX_CART_ROOT=/path/to/PhoenixCart
composer test
```

## Layout

| Path | Purpose |
|------|---------|
| `tests/bootstrap.php` | Locates catalog root, registers `catalog_autoloader` |
| `tests/Support/PhoenixTestCase.php` | Base test case |
| `tests/Unit/` | Unit tests by area (`Html/`, `Support/`, `Template/`, …) |

## License

GPL-2.0-or-later, same as Phoenix Cart.
