<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_shipping_modules_test extends http_test_case {

    protected function tearDown(): void {
        http_checkout_fixture_sql::restore_flat_shipping_geo_zone();
        http_checkout_fixture_sql::restore_shipping_modules();
        http_checkout_fixture_sql::restore_free_shipping();
        parent::tearDown();
    }

    public function test_extra_shipping_modules_list_rates_and_item_checkout_completes(): void {
        http_checkout_fixture_sql::install_extra_shipping_modules();

        $this->login_fixture_customer();

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $shipping_html = $shipping_page->getContent(false);
        $this->assertStringContainsString('Per Item', $shipping_html);
        $this->assertStringContainsString('Zone Rates', $shipping_html);
        $this->assertStringContainsString('Table Rate', $shipping_html);

        $shipping_formid = self::parse_hidden_input($shipping_html, 'formid');
        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'item_item',
            ],
        ]);
        $payment_html = $payment_page->getContent(false);
        $payment_formid = self::parse_hidden_input($payment_html, 'formid');

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $confirm_formid = self::parse_hidden_input($confirmation_page->getContent(false), 'formid');

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $this->assertStringContainsString('checkout_success.php', (string) ($success->getInfo('url') ?? ''));
        $this->assertSame(
            'Cash on Delivery',
            http_orders_lookup::latest_payment_method_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );
    }

    public function test_flat_shipping_hidden_outside_zone_while_item_remains_available(): void {
        http_checkout_fixture_sql::install_extra_shipping_modules();
        http_checkout_fixture_sql::restrict_flat_shipping_to_non_fixture_geo_zone();

        $this->login_fixture_customer();

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_html = $this->get_http()->request('GET', '/checkout_shipping.php')->getContent(false);
        $this->assertStringNotContainsString('Flat Rate', $shipping_html);
        $this->assertStringContainsString('Per Item', $shipping_html);
    }

    public function test_free_shipping_zeroes_shipping_on_confirmation(): void {
        http_checkout_fixture_sql::enable_free_shipping_over_one_dollar();

        $this->login_fixture_customer();

        $orders_id_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

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
        $confirmation_html = $confirmation_page->getContent(false);

        $this->assertTrue(
            str_contains($confirmation_html, '$0.00') || str_contains($confirmation_html, '0.00'),
            'confirmation should show zero shipping when free shipping qualifies'
        );

        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');
        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);
        $this->assertStringContainsString('checkout_success.php', (string) ($success->getInfo('url') ?? ''));

        $orders_id_after = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan($orders_id_before, $orders_id_after);

        $shipping_row = http_orders_lookup::ot_row_for_order($orders_id_after, 'ot_shipping');
        $this->assertNotNull($shipping_row);
        $this->assertEqualsWithDelta(0.0, $shipping_row['value'], 0.0001);
    }

}
