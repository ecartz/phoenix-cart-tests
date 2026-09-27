# Classes skipped because they require DB, checkout, or full shop bootstrap.

| Class / area | Reason | Wave |
|--------------|--------|------|
| `abstract_module::check()` | Covered by Integration tests (`abstract_module_check_test`) | — |
| `abstract_module::install` / `remove` | Covered by Integration (`abstract_module_install_remove_test`) | — |
| Product / cart listing / GDPR / navbar / login-form content modules | Need cart/session objects or heavy shop globals (breadcrumb **product SQL path** covered in Integration) | 3 / 4+ |

Note: `Date::expound()` / `Date::abridge()` are covered with stub `$GLOBALS['*_date_formatter']` objects for valid dates; invalid zero-dates assert `false` on CE upstream (no `strict_types` in `Date`, so a false timestamp is treated as falsy).

**Wave 2b covered (mock `$GLOBALS['db']`):** `read_configuration` via `configuration_test_helper`; `Template::build_blocks()` with enabled header_tags/boxes modules; `get_content_modules` from mock-loaded `MODULE_CONTENT_INSTALLED`; `Country` / `Zone` / `Tax::fetch_classes` / `currencies` / `language::load_all` / `Product::fetch_name` / `info_pages` helpers; `abstract_module::isEnabled()`; `cm_header_breadcrumb` Schema paths.

**Wave 3 part 1 covered (real MySQL, `tests/integration/`):** fixture import smoke; `Tax::fetch` / `get` (FL 7% seed); `abstract_module::check()`; `database_core::perform` on `configuration`.

**Wave 3 part 2 covered:** CE sample SQL import; `info_pages` JOIN/helpers; `Product::fetch_name` against sample products.

**Wave 3 part 3 covered:** `abstract_module::install` / `remove` on throwaway module keys; `Tax::fetch` zero-rate / unknown zone paths.

**Wave 3 part 4 covered:** `cm_header_breadcrumb` product path with sample SQL + real `execute()` / Schema output.

**Wave 4 part 4 covered (Http):** install footer pages via `info.php?pages_id=` after `publish_info_pages.sql`; `cookie_usage.php` slug page from seed.

**Wave 4 HTTP redirect coverage:** `Href::redirect()` is exercised via **`href_redirect_test.php`** (302 `Location` on unpublished/missing info pages, missing `products_id`, and `buy_now` → `shopping_cart.php`); not called directly in PHPUnit because it sends headers and exits.

**Wave 4 HTTP session security (finish):** `Request::check_user_agent()` / `check_ip()` via **`request_security_test.php`** (302 → `login.php` after session + mismatch); **`ssl_check.php`** slug page in **`info_page_test.php`**. Requires **`fixtures/http/enable_session_security_checks.sql`**.

**Wave 7 HTTPS (optional):** `Request::check_ssl_session_id()` via **`tests/https/ssl_session_id_test.php`** and **`composer test:https`** when **`PHOENIX_HTTPS_ENABLED=1`**, Apache **`scripts/https-server.sh`**, and **`fixtures/http/enable_ssl_session_check.sql`**. See [`documents/wave-7-design-brief.md`](documents/wave-7-design-brief.md).

**Wave 6 payment sandbox (optional):** Stripe SCA test keys in env / CI secrets; **`payment_sandbox_stripe_config_test.php`**. Full checkout + Stripe.js iframe flows remain out of scope.

Covered thin content modules (wave 2 style, hand `define()` + stub `Template` / `Linker` / `messageStack` / `$page` / `navigationHistory`): `cm_footer_text`, `cm_login_title`, `cm_cas_title`, `cm_account_title`, `cm_announcement`, `cm_footer_extra_copyright`, `cm_footer_information_links`, `cm_footer_contact_us`, `cm_footer_account`, `cm_cas_message`, `cm_cas_continue_button`, `cm_header_messagestack`, `cm_footer_extra_icons`, `cm_info_title`, `cm_info_text`, `cm_sc_title`, `cm_i_title`, `cm_t_title`, `cm_cs_title`, `cm_pinf_message`, `cm_forgot_password`.
