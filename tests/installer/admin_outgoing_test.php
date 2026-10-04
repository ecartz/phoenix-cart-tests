<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_action_recorder_fixture_sql;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_outgoing_lookup;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_outgoing_test extends install_test_case {

    private const CUSTOMER_FIRSTNAME = 'Outgoing';

    private const CUSTOMER_LASTNAME = 'Queue';

    private const CUSTOMER_EMAIL = 'phoenix-install-outgoing@example.com';

    private const CUSTOMER_PASSWORD = 'phoenix-install-test';

    private const CONTACT_ENQUIRY = 'Installer outgoing queue test enquiry from contact_us.';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_outgoing_queue_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/outgoing.php', [], 'Outgoing Queue');
    }

    public function test_admin_outgoing_email_templates_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/outgoing_tpl.php',
            [],
            'Outgoing E-mail Templates',
        );
    }

    public function test_admin_layout_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'layout'],
            'Layout',
        );
    }

    public function test_admin_update_currency_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'currencies', 'list' => 'new'],
            'c_ecb',
        );
    }

    public function test_storefront_contact_us_reaches_mail_capture_not_outgoing_queue(): void {
        $this->require_installer_mail_capture();
        installer_action_recorder_fixture_sql::clear_module('ar_contact_us');
        $this->clear_captured_mail();

        $shop_http = installer_bootstrap::client();
        $this->submit_storefront_contact($shop_http);

        $this->assert_captured_mail_contains(self::CONTACT_ENQUIRY);
        $this->assertFalse(
            installer_outgoing_lookup::has_queued_row('visitor-outgoing@example.com', 'order_thanks'),
            'Contact us should not enqueue outgoing rows for the visitor email.'
        );
    }

    public function test_storefront_checkout_success_enqueues_order_thanks_outgoing_row(): void {
        $shop_http = installer_bootstrap::client();
        $this->register_storefront_customer($shop_http);
        $this->complete_cod_checkout($shop_http);

        $success = $shop_http->request('GET', '/checkout_success.php');
        $this->assertSame(200, $success->getStatusCode());

        $this->assertTrue(
            installer_outgoing_lookup::has_queued_row(self::CUSTOMER_EMAIL, 'order_thanks'),
            'Expected order_thanks slug in outgoing after checkout_success.'
        );

        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/outgoing.php', [], self::CUSTOMER_EMAIL);
        $this->assert_admin_get_page($admin_http, '/admin/outgoing.php', [], 'order_thanks');
    }

    private function submit_storefront_contact(HttpClientInterface $shop_http): void {
        $shop_http->request('GET', '/');
        $page = $shop_http->request('GET', '/contact_us.php');
        $this->assertSame(200, $page->getStatusCode());
        $html = $page->getContent(false);
        $formid = self::parse_hidden_input($html, 'formid');
        $this->assertNotSame('', $formid);

        $response = $shop_http->request('POST', '/contact_us.php', [
            'body' => [
                'action' => 'send',
                'formid' => $formid,
                'name' => 'Outgoing Visitor',
                'email' => 'visitor-outgoing@example.com',
                'enquiry' => self::CONTACT_ENQUIRY,
            ],
        ]);
        $this->assertContains($response->getStatusCode(), [200, 302]);
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

    private function complete_cod_checkout(HttpClientInterface $shop_http): void {
        $shop_http->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $shop_http->request('GET', '/checkout_shipping.php');
        $shipping_formid = self::parse_hidden_input($shipping_page->getContent(false), 'formid');
        $this->assertNotSame('', $shipping_formid);

        $payment_page = $shop_http->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);
        $payment_formid = self::parse_hidden_input($payment_page->getContent(false), 'formid');
        $this->assertNotSame('', $payment_formid);

        $confirmation_page = $shop_http->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $confirm_formid = self::parse_hidden_input($confirmation_page->getContent(false), 'formid');
        $this->assertNotSame('', $confirm_formid);

        $success = $shop_http->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $this->assertStringContainsString('checkout_success.php', (string) ($success->getInfo('url') ?? ''));
    }

}
