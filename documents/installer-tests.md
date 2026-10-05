# Installer acceptance tests

Optional **web installer wizard** coverage against a **disposable** catalog copy and a dedicated database. This suite does **not** use the shared `phoenix_test` database or the HTTP fixture shop on port 8765.

## Location and command

| Item | Value |
|------|--------|
| Directory | [`tests/installer/`](../tests/installer/) |
| Group | **`#[Group('installer')]`** |
| PHPUnit testsuite | `installer` |
| Run | **`composer test:installer`** (prepare catalog, reset DB, start server, PHPUnit, cleanup) |

The installer suite is **not** part of **`composer test:stack`** or **`composer cloud-test`**. GitHub Actions runs it as the parallel **`installer`** job in [`.github/workflows/phpunit-mysql.yml`](../.github/workflows/phpunit-mysql.yml) via **`composer test:installer`** (separate from the **`full-stack`** job so Playwright failures do not hide installer results).

## Prerequisites

- Cloned catalog at **`PHOENIX_CART_ROOT`** (default `./PhoenixCart`)
- MySQL/MariaDB credentials (**`PHOENIX_DB_*`**) with permission to `CREATE DATABASE`, or local **`mysql -u root`** socket access for **`scripts/reset-installer-database.sh`** (MariaDB rejects TCP `root@127.0.0.1` when `unix_socket` auth is enabled). On GitHub Actions, set **`PHOENIX_MYSQL_ROOT_PASSWORD`** (the workflow uses `root` against the MariaDB service) so reset connects over TCP and grants **`phoenix@%`** on **`phoenix_install`**
- Bash (for **`scripts/run-installer-tests.sh`**)

## Environment

| Variable | Default | Purpose |
|----------|---------|---------|
| **`PHOENIX_INSTALLER_ENABLED`** | (unset) | Must be `1` for PHPUnit to run (set by `composer test:installer`) |
| **`PHOENIX_INSTALLER_BASE_URL`** | `http://127.0.0.1:8766` | Origin of the disposable catalog server |
| **`PHOENIX_INSTALLER_CATALOG_ROOT`** | `working/installer-catalog` | Gitignored copy the installer mutates |
| **`PHOENIX_INSTALLER_DB_NAME`** | `phoenix_install` | Empty database created/dropped per run |
| **`PHOENIX_DB_HOST`**, **`PHOENIX_DB_USER`**, **`PHOENIX_DB_PASSWORD`**, **`PHOENIX_DB_PORT`** | same as integration | Server credentials for `rpc.php` and step 4 |
| **`PHOENIX_MYSQL_ROOT_PASSWORD`** | (unset) | When set, **`reset-installer-database.sh`** and PHP reset use TCP **`root`** and grant **`phoenix@%`** (GitHub Actions); when unset, socket **`root`** and **`phoenix@localhost`** (Cloud/local MariaDB) |
| **`PHOENIX_INSTALLER_MAIL_CAPTURE`** | (unset) | Set to **`1`** by **`scripts/run-installer-tests.sh`** / **`scripts/installer-server.sh`** so **`admin_mail_test.php`** can assert captured messages (Linux **`sendmail_path`**) |
| **`PHOENIX_INSTALLER_MAIL_DIR`** | `working/installer-mail` | Directory where [`scripts/capture-installer-mail.php`](../scripts/capture-installer-mail.php) writes captured **`mail()`** output (gitignored) |

## Manual run

```bash
bash scripts/prepare-installer-catalog.sh
bash scripts/reset-installer-database.sh
bash scripts/installer-server.sh   # separate terminal
export PHOENIX_INSTALLER_ENABLED=1
export PHOENIX_INSTALLER_BASE_URL=http://127.0.0.1:8766
vendor/bin/phpunit --testsuite installer
```

## What is tested

[`install_wizard_test.php`](../tests/installer/install_wizard_test.php) drives `install/rpc.php` (`dbCheck`, `dbImport` with sample catalog data), POSTs installer steps 2–4, asserts the storefront serves sample categories, and verifies the wizard-created administrator can log into **`admin/login.php`**.

[`admin_catalog_test.php`](../tests/installer/admin_catalog_test.php) reuses the shared wizard helper ([`installer_wizard`](../tests/support/installer_wizard.php)) in `setUpBeforeClass`, then exercises authenticated admin HTTP against the disposable shop:

- **`/admin/index.php`** — dashboard (`display-4`)
- **`/admin/customers.php`** — customer list
- **`/admin/orders.php`** — order list
- **`/admin/catalog.php`** — sample catalog listing (`Citrus Fruit` under `cPath=1`)
- **`catalog.php?action=set_flag`** — toggles a sample product inactive and back (GET, as in the admin UI)

[`admin_pages_test.php`](../tests/installer/admin_pages_test.php) runs the same wizard setup, then covers additional read-only admin pages and one configuration write:

- **`/admin/configuration.php?gID=1`** — store name from the installer
- **`/admin/languages.php`**, **`/admin/countries.php`**, **`/admin/administrators.php`**
- **`/admin/modules.php?set=payment`** — sample payment modules
- **`configuration.php?action=save`** — renames the store, asserts the change, then restores the original name

[`admin_localization_test.php`](../tests/installer/admin_localization_test.php) covers localization, tax, order status, catalog metadata, and shipping modules, plus a reversible specials status toggle:

- **`/admin/currencies.php`**, **`/admin/zones.php`**, **`/admin/tax_classes.php`**, **`/admin/tax_rates.php`**, **`/admin/geo_zones.php`**, **`/admin/orders_status.php`**
- **`/admin/manufacturers.php`**, **`/admin/reviews.php`**, **`/admin/specials.php`**
- **`/admin/modules.php?set=shipping`** — flat-rate shipping module
- **`specials.php?action=set_flag`** — toggles the sample special inactive and back (GET, as in the admin UI)
- Storefront **`product_info.php?products_id=1`** shows sample oranges special **`$2.99`**

[`admin_merchandising_test.php`](../tests/installer/admin_merchandising_test.php) covers merchandising, content, and order-total modules, plus a reversible advert status toggle:

- **`/admin/advert_manager.php`**, **`/admin/products_attributes.php`**, **`/admin/products_expected.php`**
- **`/admin/testimonials.php`**, **`/admin/info_pages.php`**, **`/admin/customer_data_groups.php`**
- Storefront **`create_account.php`** shows customer-data **telephone** in **Your Personal Information**
- **`/admin/modules.php?set=order_total`** — sub-total order-total module
- **`advert_manager.php?action=set_flag`** — toggles the sample carousel advert inactive and back (GET, includes `formid` as in the admin UI)

[`admin_tools_test.php`](../tests/installer/admin_tools_test.php) covers admin tools, layout, hooks, and navbar modules, plus a reversible info-page status toggle:

- **`/admin/database_tables.php`**, **`/admin/server_info.php`**, **`/admin/security_checks.php`**, **`/admin/templates.php`**
- **`/admin/language_explorer.php`**, **`/admin/modules_hooks.php`**, **`/admin/sec_dir_permissions.php`**
- **`/admin/modules.php?set=navbar_modules`** — shopping-cart navbar module
- **`info_pages.php?action=set_flag`** — enables a sample info page, then restores inactive status (GET, includes `formid` as in the admin UI)

[`admin_reports_test.php`](../tests/installer/admin_reports_test.php) covers reports, managers, and remaining module sets, plus a reversible testimonials status toggle:

- **`/admin/stats_products_purchased.php`**, **`/admin/stats_customers.php`**, **`/admin/whos_online.php`**
- Storefront homepage hit then **`whos_online.php`** lists the session (guest or `127.0.0.1`)
- **`/admin/action_recorder.php`**, **`/admin/store_logo.php`**, **`/admin/newsletters.php`**
- **`/admin/modules.php?set=content`**, **`/admin/modules.php?set=header_tags`**, **`/admin/modules.php?set=dashboard`**, **`/admin/modules.php?set=customer_data`**
- **`testimonials.php?action=set_flag`** — toggles the sample testimonial inactive and back (GET, as in the admin UI)

[`admin_remaining_test.php`](../tests/installer/admin_remaining_test.php) covers the remaining read-only admin pages and module sets not opened in the reports class:

- **`/admin/modules.php?set=action_recorder`** — `ar_admin_login` action-recorder module
- **`/admin/modules.php?set=notifications`** — Checkout notification module
- **`/admin/pulse_analytics.php`** — Pulse analytics
- **`/admin/modules_actions.php`** — Actions module set
- **`/admin/importers.php`** — Importers list (empty table is valid)

[`admin_order_documents_test.php`](../tests/installer/admin_order_documents_test.php) places a storefront COD order (register customer, **Pears** quantity **2** via `product_info.php`, flat shipping, `payment=cod`) on the disposable shop, then asserts admin **`/admin/orders.php`** lists the customer, **`/admin/orders.php?action=edit`** shows **`2 x Pears`**, and **`/admin/invoice.php`** / **`/admin/packingslip.php`** with that order’s **`oID`** show the doubled line and customer name. The order edit screen on catalog pin **`master`** has no order-line **`update_products`** POST (status updates use **`update_order`** only). This suite does not invent that write.

[`storefront_hook_fixture_test.php`](../tests/installer/storefront_hook_fixture_test.php) copies [`fixtures/http/http_storefront_hook_marker.php`](../fixtures/http/http_storefront_hook_marker.php) into the installed catalog, toggles **`HTTP_TEST_STOREFRONT_HOOK_MARKER_STATUS`**, asserts the HTML marker on/off on the storefront homepage, and lists the hook on **`/admin/modules_hooks.php`**.

[`admin_writes_test.php`](../tests/installer/admin_writes_test.php) exercises reversible admin writes (each test restores prior state):

- **`reviews.php?action=set_flag`** — disables the sample review and restores active status; disabled review text is absent from storefront **`product_info.php`** (product 4) until re-enabled
- **`newsletters.php?action=insert`** — draft **`newsletter`** module row, then **`delete_confirm`** (no send)
- **`modules.php?set=boxes`** — install **`bm_categories`** from **`list=new`**, then remove
- **`configuration.php?gID=3`** — **`MAX_ADDRESS_BOOK_ENTRIES`** `5` → `6` → `5`

[`admin_side_effect_test.php`](../tests/installer/admin_side_effect_test.php) exercises side-effect admin tools (no mail send, backup restore, or `command_runner` execution):

- **`/admin/version_check.php`** — `Version Checker` heading plus one live-feed outcome: latest Phoenix, an upgrade-available line (`is the latest version available.`), or server failure to load versions (CI may be offline).
- **`/admin/mail.php`** — compose form only
- **`/admin/backup.php?action=backup`** — POST **`backup_now`** with `compress=no` (not `download=yes`); asserts the manager list shows a `db_`…`.sql` file. Requires a writable **`DIR_FS_BACKUP`** on the disposable catalog (no restore).
- **`/admin/backup.php`** — backup manager list smoke (`Database Backup Manager` heading)
- **`/admin/command_runner.php?cmd=help`** — available-commands list only (not `verb subject` execution)

[`admin_outgoing_test.php`](../tests/installer/admin_outgoing_test.php) covers the last read-only admin entry points and module sets not opened elsewhere:

- Storefront **`contact_us.php`** — requires installer mail capture; asserts **`working/installer-mail/`** contains the visitor e-mail and enquiry text; asserts **`installer_outgoing_lookup::combined_body()`** does not contain that visitor e-mail (shopowner contact uses **`mail()`**, not the outgoing queue)
- Storefront COD checkout + **`checkout_success.php`** — **`order_thanks`** row in **`outgoing`** and visible on **`/admin/outgoing.php`**
- **`/admin/outgoing.php`** — outgoing queue list smoke
- **`/admin/outgoing_tpl.php`** — sample outgoing e-mail templates
- **`/admin/modules.php?set=layout`** — layout (`&pi;`) modules
- **`/admin/modules.php?set=currencies`** — **`c_ecb`** update-currency module

[`admin_forms_test.php`](../tests/installer/admin_forms_test.php) exercises admin entity forms on the disposable shop (each flow restores or removes test data):

- **`catalog.php?action=insert_product`** / **`delete_product_confirm`** on `cPath=1` — add **`Phoenix Installer Product`**, then delete from category `1`
- Storefront register + COD order, then **`customers.php?action=update`** — last name **`CustomerEdited`** → **`Customer`**
- **`orders.php?action=update_order`** — status **Processing** with a comment, then **Pending** (no **`notify`** / mail)

[`admin_mail_test.php`](../tests/installer/admin_mail_test.php) asserts admin mail is captured under **`working/installer-mail/`** (Linux **`sendmail_path`** on [`scripts/installer-server.sh`](../scripts/installer-server.sh), not delivered to a real inbox):

- **`mail.php`** preview and **`send_email_to_user`** to the registered storefront customer
- Locked newsletter **`confirm_send`** (customer opted into the newsletter at registration)
- **`orders.php?action=update_order`** with **`notify=on`** after COD checkout

[`admin_reference_writes_test.php`](../tests/installer/admin_reference_writes_test.php) inserts and deletes localization and catalog metadata rows (each entity uses a unique name, asserts the list, then **`delete_confirm`**):

- **`languages.php`**, **`countries.php`**, **`zones.php`**, **`tax_classes.php`**, **`tax_rates.php`**, **`geo_zones.php`** (`new_zone` / **`insert_zone`** / **`delete_confirm_zone`**), **`currencies.php`**, **`manufacturers.php`**, **`orders_status.php`**

[`admin_content_writes_test.php`](../tests/installer/admin_content_writes_test.php) registers a storefront customer, then inserts and deletes content rows (no outbound mail), including an admin review visible on storefront **`product_info.php`** before delete:

- **`specials.php?action=insert`** on sample product **Pears** with a short expiry
- **`reviews.php?action=add_new`**, **`testimonials.php?action=add_new`**, **`info_pages.php?action=add_new`**, **`advert_manager.php?action=add_new`** (HTML text advert), **`outgoing_tpl.php?action=insert`**

[`admin_people_test.php`](../tests/installer/admin_people_test.php) covers people and orders:

- **`administrators.php?action=insert`** / **`delete_confirm`**
- Storefront customer, then **`customers.php?action=delete_confirm`**
- Second customer with COD checkout, then **`orders.php?action=delete_confirm`**

[`admin_modules_config_test.php`](../tests/installer/admin_modules_config_test.php) installs one module from **`list=new`** per **`modules.php`** set (skips empty sets), removes it, and round-trips one plain **`configuration_value`** key in visible groups **`4`**, **`7`**, **`8`**, **`9`**, **`10`**, **`12`**, **`13`**, **`14`**, **`15`**, and **`16`** (groups **`1`**, **`3`**, hidden **`6`**, and invisible **`11`** are skipped).

[`admin_order_line_editor_test.php`](../tests/installer/admin_order_line_editor_test.php) skips before the wizard when the pinned edit view has no line quantity, price, or add/remove controls (current pin **`master`**). When the live **`/admin/orders.php?action=edit`** form renders those inputs, the test places a storefront COD Pears order, POSTs the form’s real field names and action, and asserts quantity, price, and a changed grand total on **`/admin/invoice.php`**. Add and remove lines run only when the edit HTML includes those controls. A skip is not editor coverage — see [`coverage-gaps.md`](coverage-gaps.md).

[`admin_catalog_writes_test.php`](../tests/installer/admin_catalog_writes_test.php) exercises reversible catalog writes on the disposable shop:

- **`catalog.php?action=insert_category`** / **`delete_category_confirm`** under **`cPath=1`**
- **`insert_product`**, **`update_product`** (renamed product), **`delete_product_confirm`**
- **`copy_to_confirm`** with **`copy_as=duplicate`** into the new category (copy deleted afterward)
- **`move_product_confirm`** into the new category and back to category **`1`**
- **`update_product`** on an active product — storefront **`product_info.php`** shows the saved price (**`$6.41`**) and description
- **`products_image`** multipart upload on that same form (`enctype="multipart/form-data"`, file input **`products_image`**) — storefront **`product_info.php`** shows **`images/phoenix-installer-catalog-product.png`**. If that form is not a normal multipart post the HTTP client can send, the image test is skipped and reports that upload is blocked.

[`storefront_account_validation_test.php`](../tests/installer/storefront_account_validation_test.php) exercises **`create_account.php`** on the disposable shop. Sample data does not install **`cd_password_confirmation`**, so the class installs that customer-data module first. It then asserts:

- empty required **firstname** stays on the form and stores no customer
- a second registration with the same e-mail is rejected
- **`password_confirmation`** that does not match **`password`** is rejected
- **`matc`** omitted does not create a customer (the catalog pin references undefined **`ENTRY_MATC_ERROR`**, so the request may fail closed)
- one United States address stores **`entry_country_id` `223`** and Florida’s **`entry_zone_id`** with an empty **`entry_state`**
- one United Kingdom address (no zones) stores **`entry_country_id` `222`**, **`entry_zone_id` `0`**, and free-text **`entry_state`**

[`admin_attributes_test.php`](../tests/installer/admin_attributes_test.php) on **`products_attributes.php`** adds an option and value, links them to sample product **Pears** (`products_id=3`) with a **`+`** price prefix, then removes the link, value, and option.

[`admin_store_logo_test.php`](../tests/installer/admin_store_logo_test.php) uploads [`fixtures/installer-store-logo-test.png`](../fixtures/installer-store-logo-test.png) via **`store_logo.php?action=save`**, asserts **`STORE_LOGO`** and **`/admin/store_logo.php`** show the new file, then re-uploads the backed-up original logo from the disposable catalog copy.

Each test class runs an independent wizard install after [`install_test_case`](tests/support/install_test_case.php) resets **`phoenix_install`** (twenty-four classes → twenty-four installs per full **`composer test:installer`** run). Each test method logs in again via [`login_installed_admin()`](../tests/support/install_test_case.php) (fresh cookie jar per method). [`ensure_install_directory()`](../tests/support/installer_bootstrap.php) restores **`install/`** on the catalog copy when a prior run removed it.

Step 1’s browser `fetch` calls are exercised directly via HttpClient (no Playwright). **`rpc.php` passes the database password in the query string** — do not log request URLs.

Install step 4 overwrites **`includes/configure.php`** and **`admin/includes/configure.php`** only under the disposable copy, never under your main **`PhoenixCart`** clone.

See [`SKIPPED.md`](../SKIPPED.md) for hosted payment providers excluded from storefront checkout tests (PayPal, Stripe, 2Checkout).
