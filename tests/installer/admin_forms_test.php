<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_admin_writes;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_forms_test extends install_test_case
{
    use installer_admin_writes;

    private const PRODUCT_NAME = 'Phoenix Installer Product';

    private const PRODUCT_DESCRIPTION = 'Installer acceptance test catalog product.';

    private const CUSTOMER_FIRSTNAME = 'Forms';

    private const CUSTOMER_LASTNAME = 'Customer';

    private const CUSTOMER_LASTNAME_EDITED = 'CustomerEdited';

    private const CUSTOMER_EMAIL = 'phoenix-install-forms@example.com';

    private const CUSTOMER_PASSWORD = 'phoenix-install-test';

    private const CATEGORY_PATH = '1';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_catalog_inserts_and_deletes_product(): void
    {
        $admin_http = $this->login_installed_admin();

        $new_product = $admin_http->request('GET', '/admin/catalog.php', [
            'query' => [
                'cPath' => self::CATEGORY_PATH,
                'action' => 'new_product',
            ],
        ]);
        $this->assertSame(200, $new_product->getStatusCode());
        $new_html = $new_product->getContent(false);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);
        $language_id = $this->parse_products_name_language_id($new_html);
        $products_date_added = self::parse_hidden_input($new_html, 'products_date_added');
        $this->assertNotSame('', $products_date_added);

        $insert = $admin_http->request('POST', '/admin/catalog.php', [
            'query' => [
                'cPath' => self::CATEGORY_PATH,
                'action' => 'insert_product',
            ],
            'body' => $this->new_product_post_body($formid, $language_id, $products_date_added),
        ]);
        $this->assertContains($insert->getStatusCode(), [200, 302]);
        $insert_url = (string) ($insert->getInfo('url') ?? '');
        $product_id = $this->parse_product_id_from_url_or_catalog($insert_url, '');

        $catalog_list = $admin_http->request('GET', '/admin/catalog.php', [
            'query' => ['cPath' => self::CATEGORY_PATH],
        ]);
        $this->assertSame(200, $catalog_list->getStatusCode());
        $list_html = $catalog_list->getContent(false);
        $this->assertStringContainsString(self::PRODUCT_NAME, $list_html);
        if ($product_id === '') {
            $product_id = $this->parse_product_id_from_url_or_catalog('', $list_html);
        }
        $this->assertNotSame('', $product_id);

        $product_detail = $admin_http->request('GET', '/admin/catalog.php', [
            'query' => [
                'cPath' => self::CATEGORY_PATH,
                'pID' => $product_id,
                'action' => 'delete_product',
            ],
        ]);
        $this->assertSame(200, $product_detail->getStatusCode());
        $detail_html = $product_detail->getContent(false);
        $delete_formid = self::parse_hidden_input($detail_html, 'formid');
        $this->assertNotSame('', $delete_formid);

        $delete = $admin_http->request('POST', '/admin/catalog.php', [
            'query' => [
                'cPath' => self::CATEGORY_PATH,
                'action' => 'delete_product_confirm',
            ],
            'body' => [
                'formid' => $delete_formid,
                'products_id' => $product_id,
                'product_categories[]' => self::CATEGORY_PATH,
            ],
        ]);
        $this->assertContains($delete->getStatusCode(), [200, 302]);

        $after_delete = $admin_http->request('GET', '/admin/catalog.php', [
            'query' => ['cPath' => self::CATEGORY_PATH],
        ]);
        $this->assertSame(200, $after_delete->getStatusCode());
        $this->assertStringNotContainsString(self::PRODUCT_NAME, $after_delete->getContent(false));
    }

    public function test_admin_customer_and_order_form_round_trips(): void
    {
        $shop_http = installer_bootstrap::client();
        $this->register_storefront_customer($shop_http);
        $this->complete_cod_checkout($shop_http);

        $admin_http = $this->login_installed_admin();
        $customer_id = $this->parse_customer_id_from_list($admin_http);
        $this->update_customer_last_name(
            $admin_http,
            $customer_id,
            self::CUSTOMER_LASTNAME_EDITED,
        );
        $customers_after_edit = $admin_http->request('GET', '/admin/customers.php', [
            'query' => ['search' => self::CUSTOMER_EMAIL],
        ]);
        $this->assertSame(200, $customers_after_edit->getStatusCode());
        $this->assertStringContainsString(
            self::CUSTOMER_FIRSTNAME . ' ' . self::CUSTOMER_LASTNAME_EDITED,
            $customers_after_edit->getContent(false),
        );

        $this->update_customer_last_name($admin_http, $customer_id, self::CUSTOMER_LASTNAME);
        $customers_after_restore = $admin_http->request('GET', '/admin/customers.php', [
            'query' => ['search' => self::CUSTOMER_EMAIL],
        ]);
        $this->assertSame(200, $customers_after_restore->getStatusCode());
        $this->assertStringContainsString(
            self::CUSTOMER_FIRSTNAME . ' ' . self::CUSTOMER_LASTNAME,
            $customers_after_restore->getContent(false),
        );

        $orders_page = $admin_http->request('GET', '/admin/orders.php');
        $this->assertSame(200, $orders_page->getStatusCode());
        $order_id = $this->parse_order_id_from_orders_html($orders_page->getContent(false));

        $this->post_order_status_update($admin_http, $order_id, '2', 'Installer status note.');
        $processing_edit = $admin_http->request('GET', '/admin/orders.php', [
            'query' => [
                'oID' => $order_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $processing_edit->getStatusCode());
        $this->assertStringContainsString('Processing', $processing_edit->getContent(false));

        $this->post_order_status_update($admin_http, $order_id, '1', '');
        $pending_edit = $admin_http->request('GET', '/admin/orders.php', [
            'query' => [
                'oID' => $order_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $pending_edit->getStatusCode());
        $this->assertStringContainsString('Pending', $pending_edit->getContent(false));
    }

    /**
     * @return array<string, string>
     */
    private function new_product_post_body(
        string $formid,
        string $language_id,
        string $products_date_added,
    ): array {
        return [
            'formid' => $formid,
            'products_date_added' => $products_date_added,
            'products_status' => '0',
            'products_quantity' => '1',
            'products_date_available' => '',
            'manufacturers_id' => '',
            'importers_id' => '',
            'products_model' => 'INSTALL-TEST',
            'products_tax_class_id' => '1',
            'products_price' => '1.00',
            'products_price_gross' => '1.00',
            'products_weight' => '0.5',
            'products_gtin' => '',
            "products_name[{$language_id}]" => self::PRODUCT_NAME,
            "products_description[{$language_id}]" => self::PRODUCT_DESCRIPTION,
            "products_url[{$language_id}]" => '',
            "products_seo_title[{$language_id}]" => '',
            "products_seo_description[{$language_id}]" => '',
            "products_seo_keywords[{$language_id}]" => '',
        ];
    }

    private function parse_products_name_language_id(string $html): string
    {
        if (preg_match('/name="products_name\[(\d+)\]"/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '1';
    }

    private function parse_product_id_from_url_or_catalog(string $url, string $catalog_html): string
    {
        if (preg_match('/[?&]pID=(\d+)/', $url, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/[?&]pID=(\d+)/', $catalog_html, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private function parse_customer_id_from_list(HttpClientInterface $admin_http): string
    {
        $customers_page = $admin_http->request('GET', '/admin/customers.php', [
            'query' => ['search' => self::CUSTOMER_EMAIL],
        ]);
        $this->assertSame(200, $customers_page->getStatusCode());
        $html = $customers_page->getContent(false);
        $this->assertStringContainsString(self::CUSTOMER_EMAIL, $html);

        if (preg_match('/[?&]cID=(\d+)/', $html, $matches) === 1) {
            return $matches[1];
        }

        $this->fail('customers.php did not expose cID for the storefront customer');
    }

    private function update_customer_last_name(
        HttpClientInterface $admin_http,
        string $customer_id,
        string $last_name,
    ): void {
        $edit_page = $admin_http->request('GET', '/admin/customers.php', [
            'query' => [
                'cID' => $customer_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $edit_page->getStatusCode());
        $edit_html = $edit_page->getContent(false);
        $body = $this->parse_admin_edit_form_body($edit_html, [
            'lastname' => $last_name,
            'password' => '',
            'password_confirmation' => '',
        ]);
        $this->assertArrayHasKey('formid', $body);
        $this->assertNotSame('', $body['formid']);

        $update = $admin_http->request('POST', '/admin/customers.php', [
            'query' => [
                'cID' => $customer_id,
                'action' => 'update',
            ],
            'body' => $body,
        ]);
        $this->assertContains($update->getStatusCode(), [200, 302]);
    }

    private function post_order_status_update(
        HttpClientInterface $admin_http,
        string $order_id,
        string $status_id,
        string $comments,
    ): void {
        $edit_page = $admin_http->request('GET', '/admin/orders.php', [
            'query' => [
                'oID' => $order_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $edit_page->getStatusCode());
        $edit_html = $edit_page->getContent(false);
        $formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $formid);

        $update = $admin_http->request('POST', '/admin/orders.php', [
            'query' => [
                'oID' => $order_id,
                'action' => 'update_order',
            ],
            'body' => [
                'formid' => $formid,
                'status' => $status_id,
                'comments' => $comments,
            ],
        ]);
        $this->assertContains($update->getStatusCode(), [200, 302]);
    }

    private function register_storefront_customer(HttpClientInterface $shop_http): void
    {
        $shop_http->request('GET', '/');

        $create_account_page = $shop_http->request('GET', '/create_account.php');
        $this->assertSame(200, $create_account_page->getStatusCode());
        $create_html = $create_account_page->getContent(false);
        $formid = self::parse_hidden_input($create_html, 'formid');
        $this->assertNotSame('', $formid);

        $registered = $shop_http->request('POST', '/create_account.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => self::CUSTOMER_FIRSTNAME,
                'lastname' => self::CUSTOMER_LASTNAME,
                'email_address' => self::CUSTOMER_EMAIL,
                'password' => self::CUSTOMER_PASSWORD,
                'street_address' => '1 Test Street',
                'city' => 'Testville',
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
                'telephone' => '555-0100',
                'matc' => '1',
            ],
        ]);
        $this->assertContains($registered->getStatusCode(), [200, 302]);
    }

    private function complete_cod_checkout(HttpClientInterface $shop_http): void
    {
        $shop_http->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $shop_http->request('GET', '/checkout_shipping.php');
        $this->assertSame(200, $shipping_page->getStatusCode());
        $shipping_html = $shipping_page->getContent(false);
        $shipping_formid = self::parse_hidden_input($shipping_html, 'formid');
        $this->assertNotSame('', $shipping_formid);

        $payment_page = $shop_http->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);
        $this->assertSame(200, $payment_page->getStatusCode());
        $payment_html = $payment_page->getContent(false);
        $payment_formid = self::parse_hidden_input($payment_html, 'formid');
        $this->assertNotSame('', $payment_formid);

        $confirmation_page = $shop_http->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $this->assertSame(200, $confirmation_page->getStatusCode());
        $confirmation_html = $confirmation_page->getContent(false);
        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');
        $this->assertNotSame('', $confirm_formid);

        $success = $shop_http->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);
        $final_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $final_url);
    }

    private function parse_order_id_from_orders_html(string $html): string
    {
        if (preg_match('/[?&]oID=(\d+)/', $html, $matches) === 1) {
            return $matches[1];
        }

        $this->fail('orders.php did not contain an order link with oID');
    }
}
