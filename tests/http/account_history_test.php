<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_account_history_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_history_test extends http_test_case {

    protected function tearDown(): void {
        http_account_history_fixture_sql::remove_other_customer_order();
        parent::tearDown();
    }

    public function test_order_history_lists_cod_order_and_refuses_other_customers_order(): void {
        $this->login_fixture_customer();
        $this->complete_cod_checkout_for_pears();

        $orders_id = http_orders_lookup::latest_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan(0, $orders_id);

        $history = $this->get_http()->request('GET', '/account_history.php');
        $this->assertSame(200, $history->getStatusCode());
        $history_body = $history->getContent(false);
        $this->assertStringContainsString('Order History', $history_body);
        $this->assertStringContainsString((string) $orders_id, $history_body);
        $this->assertStringContainsString('Pears', $history_body);

        $other_order_id = http_account_history_fixture_sql::other_customer_order_id();

        $foreign = $this->get_http_without_redirects()->request('GET', '/account_history_info.php', [
            'query' => [
                'order_id' => (string) $other_order_id,
            ],
        ]);
        $this->assertSame(302, $foreign->getStatusCode());
        $location = $foreign->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString('account_history.php', $location);
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

        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);
        $payment_formid = self::parse_hidden_input($payment_page->getContent(false), 'formid');

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $confirm_formid = self::parse_hidden_input($confirmation_page->getContent(false), 'formid');

        $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);
    }

}
