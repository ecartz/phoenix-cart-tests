<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\catalog_order_editor_probe;
use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_order_line_editor_test extends install_test_case {

    private const CUSTOMER_FIRSTNAME = 'LineEdit';

    private const CUSTOMER_LASTNAME = 'Customer';

    private const CUSTOMER_EMAIL = 'phoenix-install-line-editor@example.com';

    private const CUSTOMER_PASSWORD = 'phoenix-install-test';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        if (!catalog_order_editor_probe::pinned_edit_view_has_line_editor_controls()) {
            self::markTestSkipped(
                'Pinned catalog order edit view has no line quantity, price, or add/remove controls (see fixtures/catalog_pin.txt).'
            );
        }

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_order_line_edit_updates_invoice_when_form_has_line_inputs(): void {
        $shop_http = installer_bootstrap::client();
        $this->register_storefront_customer($shop_http);
        $this->complete_cod_checkout_with_pears_quantity($shop_http, 1);

        $admin_http = $this->login_installed_admin();
        $orders_page = $admin_http->request('GET', '/admin/orders.php');
        $this->assertSame(200, $orders_page->getStatusCode());
        $order_id = $this->parse_order_id_from_orders_html($orders_page->getContent(false));

        $order_edit = $admin_http->request('GET', '/admin/orders.php', [
            'query' => [
                'oID' => $order_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $order_edit->getStatusCode());
        $edit_html = $order_edit->getContent(false);

        if (!catalog_order_editor_probe::edit_html_has_line_editor_controls($edit_html)) {
            $this->markTestSkipped(
                'Live admin order edit HTML has no line quantity, price, or add/remove controls on this catalog pin.'
            );
        }

        $invoice_before = $this->fetch_invoice_html($admin_http, $order_id);
        $grand_total_before = $this->parse_invoice_grand_total($invoice_before);

        $post_body = $this->build_line_editor_post_body_from_edit_html($edit_html, $order_id, 2, '6.24');
        $this->assertNotEmpty($post_body, 'Could not parse line-editor fields from order edit HTML');

        $update = $admin_http->request('POST', '/admin/orders.php', [
            'body' => $post_body,
        ]);
        $this->assertContains($update->getStatusCode(), [200, 302]);

        $invoice_after = $this->fetch_invoice_html($admin_http, $order_id);
        $this->assertMatchesRegularExpression(
            '/<td[^>]*>\s*2\s*<\/td>/',
            $invoice_after,
            'invoice should show updated line quantity'
        );
        $this->assertStringContainsString('6.24', $invoice_after);

        $grand_total_after = $this->parse_invoice_grand_total($invoice_after);
        $this->assertNotSame('', $grand_total_before);
        $this->assertNotSame('', $grand_total_after);
        $this->assertNotSame($grand_total_before, $grand_total_after);

        if (preg_match('/add_order_product/i', $edit_html) === 1) {
            $this->exercise_add_product_line_if_present($admin_http, $order_id, 'Oranges');
        }

        if (preg_match('/remove_order_product/i', $edit_html) === 1) {
            $this->exercise_remove_added_line_if_present($admin_http, $order_id, 'Oranges');
        }
    }

    /**
     * @return array<string, string>
     */
    private function build_line_editor_post_body_from_edit_html(
        string $edit_html,
        string $order_id,
        int $new_quantity,
        string $new_price,
    ): array {
        $body = [
            'oID' => $order_id,
        ];

        $action = self::parse_hidden_input($edit_html, 'action');
        if ($action !== '') {
            $body['action'] = $action;
        } else {
            $body['action'] = 'update_order';
        }

        $formid = self::parse_hidden_input($edit_html, 'formid');
        if ($formid !== '') {
            $body['formid'] = $formid;
        }

        if (preg_match('/update_products\[(\d+)\]\[qty\]/', $edit_html, $qty_match) === 1) {
            $line_id = $qty_match[1];
            $body['update_products[' . $line_id . '][qty]'] = (string) $new_quantity;
            if (preg_match('/update_products\[' . $line_id . '\]\[price\]/', $edit_html) === 1) {
                $body['update_products[' . $line_id . '][price]'] = $new_price;
            }

            return $body;
        }

        return [];
    }

    private function exercise_add_product_line_if_present(
        HttpClientInterface $admin_http,
        string $order_id,
        string $product_name,
    ): void {
        $edit = $admin_http->request('GET', '/admin/orders.php', [
            'query' => ['oID' => $order_id, 'action' => 'edit'],
        ]);
        $edit_html = $edit->getContent(false);
        if (preg_match('/add_order_product/i', $edit_html) !== 1) {
            return;
        }

        $body = ['oID' => $order_id, 'action' => 'add_order_product'];
        $formid = self::parse_hidden_input($edit_html, 'formid');
        if ($formid !== '') {
            $body['formid'] = $formid;
        }

        $admin_http->request('POST', '/admin/orders.php', ['body' => $body]);
        $invoice = $this->fetch_invoice_html($admin_http, $order_id);
        $this->assertStringContainsString($product_name, $invoice);
    }

    private function exercise_remove_added_line_if_present(
        HttpClientInterface $admin_http,
        string $order_id,
        string $product_name,
    ): void {
        $edit = $admin_http->request('GET', '/admin/orders.php', [
            'query' => ['oID' => $order_id, 'action' => 'edit'],
        ]);
        $edit_html = $edit->getContent(false);
        if (preg_match('/remove_order_product/i', $edit_html) !== 1) {
            return;
        }

        $body = ['oID' => $order_id, 'action' => 'remove_order_product'];
        $formid = self::parse_hidden_input($edit_html, 'formid');
        if ($formid !== '') {
            $body['formid'] = $formid;
        }

        $admin_http->request('POST', '/admin/orders.php', ['body' => $body]);
        $invoice = $this->fetch_invoice_html($admin_http, $order_id);
        $this->assertStringNotContainsString($product_name, $invoice);
    }

    private function fetch_invoice_html(HttpClientInterface $admin_http, string $order_id): string {
        $response = $admin_http->request('GET', '/admin/invoice.php', [
            'query' => ['oID' => $order_id],
        ]);
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

    private function parse_invoice_grand_total(string $invoice_html): string {
        if (preg_match('/Total[^0-9]*([\d]+\.\d{2,4})/i', $invoice_html, $matches) === 1) {
            return $matches[1];
        }

        return '';
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

    private function parse_order_id_from_orders_html(string $html): string {
        if (preg_match('/[?&]oID=(\d+)/', $html, $matches) === 1) {
            return $matches[1];
        }

        $this->fail('orders.php did not contain an order link with oID');
    }

}
