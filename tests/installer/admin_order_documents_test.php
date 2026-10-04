<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_order_documents_test extends install_test_case {

    private const CUSTOMER_FIRSTNAME = 'Document';

    private const CUSTOMER_LASTNAME = 'Customer';

    private const CUSTOMER_EMAIL = 'phoenix-install-order-docs@example.com';

    private const CUSTOMER_PASSWORD = 'phoenix-install-test';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_invoice_and_packingslip_show_cod_order_with_quantity_two(): void {
        $shop_http = installer_bootstrap::client();
        $customer_name = self::CUSTOMER_FIRSTNAME . ' ' . self::CUSTOMER_LASTNAME;

        $this->register_storefront_customer($shop_http);
        $this->complete_cod_checkout_with_pears_quantity($shop_http, 2);

        $admin_http = $this->login_installed_admin();
        $orders_page = $admin_http->request('GET', '/admin/orders.php');
        $this->assertSame(200, $orders_page->getStatusCode());
        $orders_html = $orders_page->getContent(false);
        $this->assertStringContainsString($customer_name, $orders_html);

        $order_id = $this->parse_order_id_from_orders_html($orders_html);

        $order_edit = $admin_http->request('GET', '/admin/orders.php', [
            'query' => [
                'oID' => $order_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $order_edit->getStatusCode());
        $edit_html = $order_edit->getContent(false);
        $this->assertStringContainsString('2 x Pears', $edit_html);

        $this->assert_admin_order_document($admin_http, '/admin/invoice.php', $order_id, $customer_name, 2);
        $this->assert_admin_order_document($admin_http, '/admin/packingslip.php', $order_id, $customer_name, 2);
    }

    private function register_storefront_customer(HttpClientInterface $shop_http): void {
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

        $account_probe = $shop_http->request('GET', '/account.php');
        $account_body = $account_probe->getContent(false);
        $this->assertStringContainsString('cm-account-title', $account_body);
    }

    private function complete_cod_checkout_with_pears_quantity(HttpClientInterface $shop_http, int $quantity): void {
        $shop_http->request('GET', '/');

        $product_page = $shop_http->request('GET', '/product_info.php', [
            'query' => ['products_id' => '3'],
        ]);
        $this->assertSame(200, $product_page->getStatusCode());
        $product_html = $product_page->getContent(false);
        $add_formid = self::parse_hidden_input($product_html, 'formid');
        $this->assertNotSame('', $add_formid);

        $shop_http->request('POST', '/product_info.php', [
            'query' => [
                'products_id' => '3',
                'action' => 'add_product',
            ],
            'body' => [
                'formid' => $add_formid,
                'products_id' => '3',
                'qty' => (string) $quantity,
            ],
        ]);

        $shipping_page = $shop_http->request('GET', '/checkout_shipping.php');
        $this->assertSame(200, $shipping_page->getStatusCode());
        $shipping_html = $shipping_page->getContent(false);
        $this->assertStringContainsString(self::CUSTOMER_FIRSTNAME, $shipping_html);
        $this->assertStringContainsString('Flat Rate', $shipping_html);

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
        $this->assertStringContainsString('Cash on Delivery', $payment_html);

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
        $this->assertStringContainsString('Pears', $confirmation_html);
        $this->assertStringContainsString('Cash on Delivery', $confirmation_html);

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

    private function parse_order_id_from_orders_html(string $html): string {
        if (preg_match('/[?&]oID=(\d+)/', $html, $matches) === 1) {
            return $matches[1];
        }

        $this->fail('orders.php did not contain an order link with oID');
    }

    private function assert_admin_order_document(
        HttpClientInterface $admin_http,
        string $path,
        string $order_id,
        string $customer_name,
        int $expected_quantity,
    ): void {
        $response = $admin_http->request('GET', $path, [
            'query' => ['oID' => $order_id],
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringNotContainsString('login.php', $final_url);
        $body = $response->getContent(false);
        $this->assertStringContainsString($expected_quantity . ' x Pears', $body);
        $this->assertStringContainsString($customer_name, $body);
    }

}
