# Classes skipped because they require DB, checkout, or full shop bootstrap.

| Class / area | Reason |
|--------------|--------|
| `Template::build_blocks` | Requires `TEMPLATE_BLOCK_GROUPS` and `MODULE_*_INSTALLED` constants from live shop configuration |
