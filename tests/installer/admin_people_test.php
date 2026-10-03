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
final class admin_people_test extends install_test_case
{
    use installer_admin_writes;

    private const ADMIN_USERNAME = 'phoenix_installer_admin';

    private const ADMIN_PASSWORD = 'phoenix-installer-admin';

    private const CUSTOMER_FIRSTNAME = 'People';

    private const CUSTOMER_LASTNAME = 'DeleteMe';

    private const CUSTOMER_EMAIL = 'phoenix-install-people@example.com';

    private const CUSTOMER_PASSWORD = 'phoenix-install-test';

    private const ORDER_CUSTOMER_FIRSTNAME = 'Order';

    private const ORDER_CUSTOMER_LASTNAME = 'DeleteTarget';

    private const ORDER_CUSTOMER_EMAIL = 'phoenix-install-order-delete@example.com';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_administrator_insert_and_delete(): void {
        $admin_http = $this->login_installed_admin();

        $new_html = $this->fetch_admin_page($admin_http, '/admin/administrators.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/administrators.php', ['action' => 'insert'], [
            'formid' => $formid,
            'username' => self::ADMIN_USERNAME,
            'password' => self::ADMIN_PASSWORD,
        ]);

        $list_html = $this->assert_admin_list_contains(
            $admin_http,
            '/admin/administrators.php',
            [],
            self::ADMIN_USERNAME,
        );
        $admin_id = $this->parse_entity_id_near_needle($list_html, self::ADMIN_USERNAME, 'aID');

        $this->confirm_admin_delete($admin_http, '/admin/administrators.php', 'aID', $admin_id);
        $this->assert_admin_list_not_contains(
            $admin_http,
            '/admin/administrators.php',
            [],
            self::ADMIN_USERNAME,
        );
    }

    public function test_admin_customer_delete_after_storefront_register(): void {
        $shop_http = installer_bootstrap::client();
        $this->register_storefront_customer(
            $shop_http,
            self::CUSTOMER_FIRSTNAME,
            self::CUSTOMER_LASTNAME,
            self::CUSTOMER_EMAIL,
        );

        $admin_http = $this->login_installed_admin();
        $customer_label = self::CUSTOMER_LASTNAME . ', ' . self::CUSTOMER_FIRSTNAME;
        $list_html = $this->assert_admin_list_contains(
            $admin_http,
            '/admin/customers.php',
            ['search' => self::CUSTOMER_EMAIL],
            $customer_label,
        );
        $customer_id = $this->parse_entity_id_near_needle(
            $list_html,
            $customer_label,
            'cID',
        );

        $this->confirm_admin_delete(
            $admin_http,
            '/admin/customers.php',
            'cID',
            $customer_id,
            'delete_confirm',
            [],
            [],
            'confirm',
        );
        $this->assert_admin_list_not_contains(
            $admin_http,
            '/admin/customers.php',
            ['search' => self::CUSTOMER_EMAIL],
            $customer_label,
        );
    }

    public function test_admin_order_delete_after_cod_checkout(): void {
        $shop_http = installer_bootstrap::client();
        $this->register_storefront_customer(
            $shop_http,
            self::ORDER_CUSTOMER_FIRSTNAME,
            self::ORDER_CUSTOMER_LASTNAME,
            self::ORDER_CUSTOMER_EMAIL,
        );
        $this->complete_cod_checkout($shop_http);

        $admin_http = $this->login_installed_admin();
        $orders_html = $this->fetch_admin_page($admin_http, '/admin/orders.php');
        $customer_label = self::ORDER_CUSTOMER_FIRSTNAME . ' ' . self::ORDER_CUSTOMER_LASTNAME;
        $this->assertStringContainsString($customer_label, $orders_html);
        $order_id = $this->parse_entity_id_near_needle($orders_html, $customer_label, 'oID');

        $this->confirm_admin_delete($admin_http, '/admin/orders.php', 'oID', $order_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/orders.php', [], $customer_label);
    }

    private function register_storefront_customer(
        HttpClientInterface $shop_http,
        string $firstname,
        string $lastname,
        string $email,
    ): void {
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
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email_address' => $email,
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

    private function complete_cod_checkout(HttpClientInterface $shop_http): void {
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
}
