<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_success_order_id_bootstrap;
use PhoenixCart\Tests\support\http_checkout_success_redirect_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_success_stale_redirect_test extends http_test_case {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        http_checkout_success_order_id_bootstrap::install_fixture_hook();
    }

    public static function tearDownAfterClass(): void {
        http_checkout_success_order_id_bootstrap::remove_fixture_hook();
        parent::tearDownAfterClass();
    }

    protected function tearDown(): void {
        http_checkout_success_redirect_fixture_sql::restore();
        parent::tearDown();
    }

    public function test_stale_checkout_success_redirects_to_account(): void {
        $this->login_fixture_customer();
        $this->complete_cod_checkout_for_pears();

        $orders_id = http_orders_lookup::latest_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan(0, $orders_id);

        http_checkout_success_redirect_fixture_sql::enable_redirect_after_minutes(30);
        http_checkout_success_redirect_fixture_sql::backdate_order($orders_id, 45);

        $response = $this->get_http_without_redirects()->request('GET', '/checkout_success.php');
        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString('account.php', $location);
    }

    public function test_recent_checkout_success_stays_on_thank_you_page(): void {
        $this->login_fixture_customer();
        $this->complete_cod_checkout_for_pears();

        http_checkout_success_redirect_fixture_sql::enable_redirect_after_minutes(30);

        $response = $this->get_http_without_redirects()->request('GET', '/checkout_success.php');
        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('cm-cs-thank-you', $body);
    }

    private function complete_cod_checkout_for_pears(): void {
        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $shipping_formid = self::parse_hidden_input($shipping_page->getContent(false), 'formid');
        $this->assertNotSame('', $shipping_formid);

        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);
        $payment_formid = self::parse_hidden_input($payment_page->getContent(false), 'formid');
        $this->assertNotSame('', $payment_formid);

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $confirm_formid = self::parse_hidden_input($confirmation_page->getContent(false), 'formid');
        $this->assertNotSame('', $confirm_formid);

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $this->assertStringContainsString('checkout_success.php', (string) ($success->getInfo('url') ?? ''));
    }

}
