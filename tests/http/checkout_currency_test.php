<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_currency_test extends http_test_case {

    private const EXPECTED_CURRENCY = 'EUR';

    private const EXPECTED_CURRENCY_VALUE = 0.8522;

    public function test_eur_checkout_stores_currency_and_value(): void {
        $this->login_fixture_customer();
        $orders_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'currency' => self::EXPECTED_CURRENCY,
            ],
        ]);

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

        $currency = http_orders_lookup::orders_currency_for_order($orders_id);
        $this->assertNotNull($currency);
        $this->assertSame(self::EXPECTED_CURRENCY, $currency['currency']);
        $this->assertSame(self::EXPECTED_CURRENCY_VALUE, $currency['currency_value']);
    }

}
