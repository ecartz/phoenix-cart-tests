<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_priced_attribute_test extends http_test_case {

    private const EXPECTED_SUBTOTAL = 12.48;

    private const EXPECTED_SHIPPING = 5.0;

    private const EXPECTED_TAX = 0.8736;

    private const EXPECTED_TOTAL = 18.3536;

    protected function tearDown(): void {
        http_checkout_fixture_sql::remove_priced_cart_attribute_for_pears();
        parent::tearDown();
    }

    public function test_checkout_with_priced_attribute_quantity_two_stores_exact_order_totals(): void {
        http_checkout_fixture_sql::insert_priced_cart_attribute_for_pears();

        $this->login_fixture_customer();

        $orders_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

        $option_id = http_checkout_fixture_sql::priced_option_id();
        $value_id = http_checkout_fixture_sql::priced_value_id();

        $this->post_add_product_to_cart(3, [
            'qty' => '2',
            'id[' . $option_id . ']' => (string) $value_id,
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
        $confirmation_html = $confirmation_page->getContent(false);
        $this->assertStringContainsString('HTTP Test Priced Option', $confirmation_html);
        $this->assertStringContainsString('HTTP Priced Add-on', $confirmation_html);

        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $this->assertStringContainsString('checkout_success.php', (string) ($success->getInfo('url') ?? ''));

        $orders_after = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan($orders_before, $orders_after);

        $this->assertSame(2, http_orders_lookup::orders_products_quantity_for_order($orders_after, 3));

        $this->assertSame(
            self::EXPECTED_SUBTOTAL,
            http_orders_lookup::orders_total_value_for_order($orders_after, 'ot_subtotal')
        );
        $this->assertSame(
            self::EXPECTED_SHIPPING,
            http_orders_lookup::orders_total_value_for_order($orders_after, 'ot_shipping')
        );
        $this->assertSame(
            self::EXPECTED_TAX,
            http_orders_lookup::orders_total_value_for_order($orders_after, 'ot_tax')
        );
        $this->assertSame(
            self::EXPECTED_TOTAL,
            http_orders_lookup::orders_total_value_for_order($orders_after, 'ot_total')
        );
    }

}
