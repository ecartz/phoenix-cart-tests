<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_priced_attribute_test extends http_test_case {

    protected function tearDown(): void {
        http_checkout_fixture_sql::remove_priced_attribute_for_pears();
        parent::tearDown();
    }

    public function test_cod_checkout_stores_priced_attribute_line_and_order_totals(): void {
        http_checkout_fixture_sql::insert_priced_attribute_for_pears();

        $this->login_fixture_customer();

        $orders_id_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

        $option_id = http_checkout_fixture_sql::priced_option_id();
        $value_id = http_checkout_fixture_sql::priced_value_id();

        $this->post_add_product_to_cart(3, [
            'cart_quantity' => '2',
            'id[' . $option_id . ']' => (string) $value_id,
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
        $this->assertStringContainsString('Pears', $confirmation_html);
        $this->assertStringContainsString('HTTP Priced Option', $confirmation_html);
        $this->assertStringContainsString('HTTP Priced Value', $confirmation_html);
        $this->assertStringContainsString('Cash on Delivery', $confirmation_html);
        $this->assertStringContainsString('Flat Rate', $confirmation_html);
        $this->assertStringContainsString('$12.48', $confirmation_html);
        $this->assertStringContainsString('Sub-Total', $confirmation_html);
        $this->assertStringContainsString('$4.99', $confirmation_html);
        $this->assertStringContainsString('Total', $confirmation_html);

        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');
        $this->assertNotSame('', $confirm_formid);

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $final_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $final_url);

        $orders_id_after = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan($orders_id_before, $orders_id_after);

        $this->assertSame(
            'Cash on Delivery',
            http_orders_lookup::latest_payment_method_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );

        $line = http_orders_lookup::orders_products_line_for_order($orders_id_after, 3);
        $this->assertNotNull($line);
        $this->assertSame('Pears', $line['products_name']);
        $this->assertSame(2, $line['products_quantity']);
        $this->assertEqualsWithDelta(6.24, $line['final_price'], 0.0001);

        $attributes = http_orders_lookup::orders_products_attribute_rows_for_order($orders_id_after);
        $this->assertCount(1, $attributes);
        $this->assertSame('HTTP Priced Option', $attributes[0]['products_options']);
        $this->assertSame('HTTP Priced Value', $attributes[0]['products_options_values']);
        $this->assertEqualsWithDelta(1.25, $attributes[0]['options_values_price'], 0.0001);
        $this->assertSame('+', $attributes[0]['price_prefix']);

        $subtotal = http_orders_lookup::ot_row_for_order($orders_id_after, 'ot_subtotal');
        $shipping = http_orders_lookup::ot_row_for_order($orders_id_after, 'ot_shipping');
        $tax = http_orders_lookup::ot_row_for_order($orders_id_after, 'ot_tax');
        $total = http_orders_lookup::ot_row_for_order($orders_id_after, 'ot_total');

        $this->assertNotNull($subtotal);
        $this->assertEqualsWithDelta(12.48, $subtotal['value'], 0.0001);

        $this->assertNotNull($shipping);
        $this->assertEqualsWithDelta(4.99, $shipping['value'], 0.0001);

        $this->assertNotNull($tax);
        $this->assertGreaterThan(0.0, $tax['value']);
        $this->assertStringContainsString('FL TAX', $tax['title']);

        $this->assertNotNull($total);
        $this->assertEqualsWithDelta(
            $subtotal['value'] + $shipping['value'] + $tax['value'],
            $total['value'],
            0.02
        );
    }

}
