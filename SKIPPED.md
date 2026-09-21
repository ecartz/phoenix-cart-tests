# Classes skipped because they require DB, checkout, or full shop bootstrap.

| Class / area | Reason |
|--------------|--------|
| `Date::expound()` | Requires `$GLOBALS['long_date_formatter']` from application bootstrap |
| `Date::abridge()` | Requires `$GLOBALS['short_date_formatter']` from application bootstrap |
| `Href::redirect()` | Sends HTTP headers and terminates the request |
| `Href` with `SESSION_FORCE_COOKIE_USE === 'True'` | Requires alternate shop constant bootstrap in an isolated process |
| `Image` with `IMAGE_REQUIRED === 'false'` | Requires alternate shop constant bootstrap in an isolated process |
