<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_geo_zone_cod_test extends http_test_case
{
    protected function setUp(): void
    {
        parent::setUp();
        http_checkout_fixture_sql::restrict_cod_to_non_fixture_geo_zone();
    }

    protected function tearDown(): void
    {
        http_checkout_fixture_sql::restore_cod_geo_zone();
        parent::tearDown();
    }

    public function test_cod_hidden_when_delivery_address_outside_payment_zone(): void
    {
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
        $this->assertStringNotContainsString('Cash on Delivery', $payment_html);
        $this->assertStringContainsString('Check/Money Order', $payment_html);
    }
}
