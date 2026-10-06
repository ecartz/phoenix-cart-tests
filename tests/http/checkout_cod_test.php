<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_cod_test extends http_test_case {

    private const EXPECTED_SUBTOTAL = 4.99;

    private const EXPECTED_SHIPPING = 5.0;

    private const EXPECTED_TAX = 0.3493;

    private const EXPECTED_TOTAL = 10.3393;

    public function test_logged_in_customer_completes_checkout_with_cod(): void {
        $this->login_fixture_customer();

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $this->assertSame(200, $shipping_page->getStatusCode());
        $shipping_html = $shipping_page->getContent(false);
        $this->assertStringContainsString('Fixture', $shipping_html);
        $this->assertStringContainsString('Flat Rate', $shipping_html);

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
        $this->assertStringContainsString('Cash on Delivery', $payment_html);

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
        $this->assertStringContainsString('Cash on Delivery', $confirmation_html);
        $this->assertStringContainsString('Flat Rate', $confirmation_html);
        $this->assertStringContainsString('$4.99', $confirmation_html);
        $this->assertStringContainsString('Sub-Total', $confirmation_html);
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

        $success_body = $success->getContent(false);
        $this->assertStringContainsString('cm-cs-thank-you', $success_body);

        $this->assertSame(
            'Cash on Delivery',
            http_orders_lookup::latest_payment_method_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );

        $orders_id = http_orders_lookup::latest_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $tax_row = http_orders_lookup::ot_tax_row_for_order($orders_id);
        $this->assertNotNull($tax_row);
        $this->assertStringContainsString('FL TAX', $tax_row['title']);

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

    public function test_bad_formid_on_checkout_shipping_does_not_advance_to_payment(): void {
        $this->login_fixture_customer();

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $this->assertSame(200, $shipping_page->getStatusCode());

        $response = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => '00000000000000000000000000000000',
                'shipping' => 'flat_flat',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('Flat Rate', $body);
        $this->assertStringNotContainsString('Cash on Delivery', $body);

        $payment_probe = $this->get_http()->request('GET', '/checkout_payment.php');
        $final_url = (string) ($payment_probe->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_shipping.php', $final_url);
    }

}
