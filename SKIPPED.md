# Classes skipped because they require DB, checkout, or full shop bootstrap.

| Class / area | Reason | Wave |
|--------------|--------|------|
| `Href::redirect()` | Sends HTTP headers and terminates the request | 4+ |
| `Request::check_ssl_session_id()` / `check_user_agent()` / `check_ip()` | Destroy session and call `Href::redirect()` on mismatch | 4+ |
| `abstract_module::check()` | Covered by Integration tests (`abstract_module_check_test`) | — |
| `abstract_module::install` / `remove` | Needs `perform` / DELETE writes (later wave 3 slice) | 3 |
| Product / cart listing / GDPR / navbar / login-form content modules | Need DB rows, cart/session objects, or heavy shop globals | 3 |

Note: `Date::expound()` / `Date::abridge()` are covered with stub `$GLOBALS['*_date_formatter']` objects for valid dates; invalid zero-dates assert `false` on CE upstream (no `strict_types` in `Date`, so a false timestamp is treated as falsy).

**Wave 2b covered (mock `$GLOBALS['db']`):** `read_configuration` via `configuration_test_helper`; `Template::build_blocks()` with enabled header_tags/boxes modules; `get_content_modules` from mock-loaded `MODULE_CONTENT_INSTALLED`; `Country` / `Zone` / `Tax::fetch_classes` / `currencies` / `language::load_all` / `Product::fetch_name` / `info_pages` helpers; `abstract_module::isEnabled()`; `cm_header_breadcrumb` Schema paths.

**Wave 3 part 1 covered (real MySQL, `tests/Integration/`):** fixture import smoke; `Tax::fetch` / `get` (FL 7% seed); `abstract_module::check()`; `database_core::perform` on `configuration`.

Covered thin content modules (wave 2 style, hand `define()` + stub `Template` / `Linker` / `messageStack` / `$page` / `navigationHistory`): `cm_footer_text`, `cm_login_title`, `cm_cas_title`, `cm_account_title`, `cm_announcement`, `cm_footer_extra_copyright`, `cm_footer_information_links`, `cm_footer_contact_us`, `cm_footer_account`, `cm_cas_message`, `cm_cas_continue_button`, `cm_header_messagestack`, `cm_footer_extra_icons`, `cm_info_title`, `cm_info_text`, `cm_sc_title`, `cm_i_title`, `cm_t_title`, `cm_cs_title`, `cm_pinf_message`, `cm_forgot_password`.
