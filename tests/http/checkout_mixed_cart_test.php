<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_mixed_cart_test extends http_test_case {

    protected function setUp(): void {
        parent::setUp();
        http_checkout_fixture_sql::insert_virtual_download_for_pears();
    }

    protected function tearDown(): void {
        http_checkout_fixture_sql::remove_virtual_download_for_pears();
        parent::tearDown();
    }

    public function test_mixed_virtual_and_physical_cart_still_requires_shipping_and_offers_cod(): void {
        $this->login_fixture_customer();

        $option_id = http_checkout_fixture_sql::virtual_option_id();
        $value_id = http_checkout_fixture_sql::virtual_value_id();

        $this->post_add_product_to_cart(3, [
            'id[' . $option_id . ']' => (string) $value_id,
        ]);

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '1',
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $this->assertSame(200, $shipping_page->getStatusCode());
        $final_url = (string) ($shipping_page->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_shipping.php', $final_url);

        $shipping_html = $shipping_page->getContent(false);
        $this->assertStringContainsString('Flat Rate', $shipping_html);

        $shipping_formid = self::parse_hidden_input($shipping_html, 'formid');
        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);

        $payment_html = $payment_page->getContent(false);
        $this->assertStringContainsString('Cash on Delivery', $payment_html);
    }

}
