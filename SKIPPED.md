# Classes skipped because they require DB, checkout, or full shop bootstrap.

| Class / area | Reason |
|--------------|--------|
| `Date::expound()` | Requires `$GLOBALS['long_date_formatter']` from application bootstrap |
| `Date::abridge()` | Requires `$GLOBALS['short_date_formatter']` from application bootstrap |

