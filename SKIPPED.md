# Classes skipped because they require DB, checkout, or full shop bootstrap.

| Class / area | Reason | Wave |
|--------------|--------|------|
| `Href::redirect()` | Sends HTTP headers and terminates the request | 4+ |
| `Template::build_blocks()` with enabled modules | Executing real navbar/box modules needs module STATUS constants and side effects; empty groups covered by mock-db tests | 2b / 3 |
| `Request::check_ssl_session_id()` / `check_user_agent()` / `check_ip()` | Destroy session and call `Href::redirect()` on mismatch | 4+ |
| `cm_header_breadcrumb` | Product/category breadcrumb paths query `$GLOBALS['db']` | 2b / 3 |
| Product / cart listing / GDPR / navbar / login-form content modules | Need DB rows, cart/session objects, or heavy shop globals | 2b / 3 |

Note: `Date::expound()` / `Date::abridge()` are covered with stub `$GLOBALS['*_date_formatter']` objects for valid dates; invalid zero-dates assert `false` on CE upstream (no `strict_types` in `Date`, so a false timestamp is treated as falsy).

Covered thin content modules (wave 2 style, hand `define()` + stub `Template` / `Linker` / `messageStack` / `$page` / `navigationHistory`): `cm_footer_text`, `cm_login_title`, `cm_cas_title`, `cm_account_title`, `cm_announcement`, `cm_footer_extra_copyright`, `cm_footer_information_links`, `cm_footer_contact_us`, `cm_footer_account`, `cm_cas_message`, `cm_cas_continue_button`, `cm_header_messagestack`, `cm_footer_extra_icons`, `cm_info_title`, `cm_info_text`, `cm_sc_title`, `cm_i_title`, `cm_t_title`, `cm_cs_title`, `cm_pinf_message`, `cm_forgot_password`.
