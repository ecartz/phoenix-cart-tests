<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_order_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_history_test extends http_test_case {

    private ?int $other_customer_order_id = null;

    protected function tearDown(): void {
        http_order_fixture_sql::delete_other_customer_fixture();
        $this->other_customer_order_id = null;
        parent::tearDown();
    }

    public function test_order_history_lists_latest_cod_order(): void {
        $this->login_fixture_customer();
        $this->complete_cod_checkout_for_pears();

        $orders_id = http_orders_lookup::latest_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan(0, $orders_id);

        $history = $this->get_http()->request('GET', '/account_history.php');
        $this->assertSame(200, $history->getStatusCode());
        $body = $history->getContent(false);
        $this->assertStringContainsString('Order History', $body);
        $this->assertStringContainsString((string) $orders_id, $body);
        $this->assertStringContainsString('Pears', $body);
    }

    public function test_other_customers_order_id_redirects_away_from_detail(): void {
        $this->other_customer_order_id = http_order_fixture_sql::insert_other_customer_order();
        $this->login_fixture_customer();

        $detail = $this->get_http_without_redirects()->request('GET', '/account_history_info.php', [
            'query' => [
                'order_id' => (string) $this->other_customer_order_id,
            ],
        ]);
        $this->assertSame(302, $detail->getStatusCode());
        $location = $detail->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString('account_history.php', $location);
    }

    public function test_checkout_success_shows_thank_you_module_after_cod(): void {
        $this->login_fixture_customer();
        $this->complete_cod_checkout_for_pears();

        $success = $this->get_http()->request('GET', '/checkout_success.php');
        $this->assertSame(200, $success->getStatusCode());
        $body = $success->getContent(false);
        $this->assertStringContainsString('cm-cs-thank-you', $body);
        $this->assertStringContainsString('checkout_success', $body);
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
