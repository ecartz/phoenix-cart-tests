<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_tax_display_test extends http_test_case {

    private const EXPECTED_SUBTOTAL = 5.34;

    private const EXPECTED_SHIPPING = 5.35;

    private const EXPECTED_TAX = 0.6993;

    private const EXPECTED_TOTAL = 10.69;

    protected function tearDown(): void {
        http_checkout_fixture_sql::restore_display_price_with_tax_and_flat_shipping_tax();
        parent::tearDown();
    }

    public function test_display_price_with_tax_and_shipping_tax_store_gross_totals(): void {
        http_checkout_fixture_sql::enable_display_price_with_tax_and_flat_shipping_tax();

        $this->login_fixture_customer();
        $orders_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

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

        $orders_id = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan($orders_before, $orders_id);
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
    }

}
