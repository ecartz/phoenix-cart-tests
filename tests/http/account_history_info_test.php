<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_history_info_test extends http_test_case {

    private const EXPECTED_SUBTOTAL = 4.99;

    private const EXPECTED_SHIPPING = 5.0;

    private const EXPECTED_TAX = 0.3493;

    private const EXPECTED_TOTAL = 10.3393;

    public function test_order_detail_after_cod_checkout_and_bad_order_id_redirects(): void {
        $this->login_fixture_customer();
        $orders_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->complete_cod_checkout_for_pears();

        $orders_id = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan($orders_before, $orders_id);

        $detail = $this->get_http()->request('GET', '/account_history_info.php', [
            'query' => [
                'order_id' => (string) $orders_id,
            ],
        ]);
        $this->assertSame(200, $detail->getStatusCode());
        $detail_body = $detail->getContent(false);
        $this->assertStringContainsString('Order Information', $detail_body);
        $this->assertStringContainsString('Pears', $detail_body);
        $this->assertStringContainsString('Flat Rate', $detail_body);
        $this->assertStringContainsString('$4.99', $detail_body);
        $this->assertSame(
            'Cash on Delivery',
            http_orders_lookup::latest_payment_method_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );
        $this->assertSame(
            self::EXPECTED_SUBTOTAL,
            http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_subtotal')
        );
        $this->assertSame(
            self::EXPECTED_SHIPPING,
            http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_shipping')
        );
        $this->assertSame(
            self::EXPECTED_TAX,
            http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_tax')
        );
        $this->assertSame(
            self::EXPECTED_TOTAL,
            http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_total')
        );

        $bad_id = $this->get_http_without_redirects()->request('GET', '/account_history_info.php', [
            'query' => [
                'order_id' => 'not-an-order-id',
            ],
        ]);
        $this->assertSame(302, $bad_id->getStatusCode());
        $headers = $bad_id->getHeaders(false);
        $location = $headers['location'][0] ?? '';
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
        $this->assertSame(200, $shipping_page->getStatusCode());
        $shipping_html = $shipping_page->getContent(false);
        $shipping_formid = self::parse_hidden_input($shipping_html, 'formid');
        $this->assertNotSame('', $shipping_formid);

        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
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

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $this->assertSame(200, $confirmation_page->getStatusCode());
        $confirmation_html = $confirmation_page->getContent(false);
        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');
        $this->assertNotSame('', $confirm_formid);

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $final_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $final_url);
    }

}
