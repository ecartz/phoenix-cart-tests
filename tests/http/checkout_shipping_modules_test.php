<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_shipping_modules_test extends http_test_case {

    private const ITEM_SHIPPING_MODULE_ID = 'item_item';

    private const ITEM_SHIPPING_TITLE = 'Per Item';

    private const ITEM_SHIPPING_COST = 2.5;

    private const PEARS_SUBTOTAL = 4.99;

    private const ZONE_TABLE_SHIPPING_COST = 8.5;

    private const ZONE_TABLE_TAX = 0.3493;

    private const ZONE_TABLE_TOTAL = 13.8393;

    protected function tearDown(): void {
        http_checkout_fixture_sql::restore_flat_shipping_geo_zone();
        http_checkout_fixture_sql::restore_shipping_modules();
        http_checkout_fixture_sql::restore_free_shipping();
        parent::tearDown();
    }

    public function test_extra_shipping_modules_list_rates_and_item_checkout_completes(): void {
        http_checkout_fixture_sql::install_extra_shipping_modules();

        $this->login_fixture_customer();
        $orders_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

        $shipping_page = $this->buy_pears_and_open_checkout_shipping();
        $shipping_html = $shipping_page->getContent(false);
        $this->assertStringContainsString('Per Item', $shipping_html);
        $this->assertStringContainsString('Zone Rates', $shipping_html);
        $this->assertStringContainsString('Table Rate', $shipping_html);

        $this->complete_cod_pears_checkout_with_shipping_module(self::ITEM_SHIPPING_MODULE_ID, $shipping_page);

        $this->assertSame(
            'Cash on Delivery',
            http_orders_lookup::latest_payment_method_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );

        $orders_id = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan($orders_before, $orders_id);
        $this->assertSame(
            self::ITEM_SHIPPING_TITLE,
            http_orders_lookup::orders_total_title_for_order($orders_id, 'ot_shipping')
        );
        $this->assertSame(
            self::ITEM_SHIPPING_COST,
            http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_shipping')
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function zone_and_table_shipping_module_provider(): array {
        return [
            'zone rates' => ['zones_zones', 'Zone Rates'],
            'table rate' => ['table_table', 'Table Rate'],
        ];
    }

    #[DataProvider('zone_and_table_shipping_module_provider')]
    public function test_zone_and_table_shipping_modules_store_expected_order_totals(
        string $shipping_module_id,
        string $expected_shipping_title
    ): void {
        http_checkout_fixture_sql::install_extra_shipping_modules();

        $this->login_fixture_customer();
        $orders_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

        $this->complete_cod_pears_checkout_with_shipping_module($shipping_module_id);

        $orders_id = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan($orders_before, $orders_id);
        $shipping_title = http_orders_lookup::orders_total_title_for_order($orders_id, 'ot_shipping');
        $this->assertNotNull($shipping_title);
        $this->assertStringStartsWith($expected_shipping_title, $shipping_title);
        $this->assertSame(
            self::PEARS_SUBTOTAL,
            http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_subtotal')
        );
        $this->assertSame(
            self::ZONE_TABLE_SHIPPING_COST,
            http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_shipping')
        );
        $this->assertSame(
            self::ZONE_TABLE_TAX,
            http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_tax')
        );
        $this->assertSame(
            self::ZONE_TABLE_TOTAL,
            http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_total')
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

        $confirmation_html = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ])->getContent(false);

        $this->assertTrue(
            str_contains($confirmation_html, '$0.00') || str_contains($confirmation_html, '0.00'),
            'confirmation should show zero shipping when free shipping qualifies'
        );

        $shipping_formid = self::parse_hidden_input($confirmation_html, 'formid');
        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $shipping_formid,
            ],
        ]);
        $this->assertStringContainsString('checkout_success.php', (string) ($success->getInfo('url') ?? ''));

        $orders_id = http_orders_lookup::latest_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertSame(0.0, http_orders_lookup::orders_total_value_for_order($orders_id, 'ot_shipping'));
    }

    private function buy_pears_and_open_checkout_shipping(): \Symfony\Contracts\HttpClient\ResponseInterface {
        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        return $this->get_http()->request('GET', '/checkout_shipping.php');
    }

    private function complete_cod_pears_checkout_with_shipping_module(
        string $shipping_module_id,
        ?\Symfony\Contracts\HttpClient\ResponseInterface $shipping_page = null
    ): void {
        if ($shipping_page === null) {
            $shipping_page = $this->buy_pears_and_open_checkout_shipping();
        }
        $shipping_formid = self::parse_hidden_input($shipping_page->getContent(false), 'formid');

        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => $shipping_module_id,
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

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $this->assertStringContainsString('checkout_success.php', (string) ($success->getInfo('url') ?? ''));
    }

}
