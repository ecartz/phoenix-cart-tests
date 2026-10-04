<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_stock_test extends http_test_case {

    private const PEARS_PRODUCT_ID = 3;

    protected function tearDown(): void {
        http_checkout_fixture_sql::restore_pears_stock_and_checkout_flag();
        parent::tearDown();
    }

    public function test_checkout_process_redirects_to_cart_when_stock_blocked(): void {
        http_checkout_fixture_sql::block_checkout_when_pears_out_of_stock();

        $this->login_fixture_customer();

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $shipping_html = $shipping_page->getContent(false);
        $shipping_formid = self::parse_hidden_input($shipping_html, 'formid');

        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
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
        $confirmation_html = $confirmation_page->getContent(false);
        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');

        $blocked = $this->get_http_without_redirects()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $this->assertSame(302, $blocked->getStatusCode());
        $location = (string) ($blocked->getHeaders(false)['location'][0] ?? '');
        $this->assertStringContainsString('shopping_cart.php', $location);
    }

    public function test_successful_order_reduces_pears_quantity(): void {
        $this->login_fixture_customer();

        $before = http_checkout_fixture_sql::products_quantity(self::PEARS_PRODUCT_ID);

        try {
            $this->get_http()->request('GET', '/index.php', [
                'query' => [
                    'action' => 'buy_now',
                    'products_id' => (string) self::PEARS_PRODUCT_ID,
                ],
            ]);

            $cart = $this->get_http()->request('GET', '/shopping_cart.php');
            $cart_html = $cart->getContent(false);
            $formid = self::parse_hidden_input($cart_html, 'formid');
            $this->assertNotSame('', $formid);

            $updated = $this->get_http()->request('POST', '/shopping_cart.php', [
                'query' => [
                    'action' => 'update_product',
                ],
                'body' => [
                    'formid' => $formid,
                    'products_id[]' => (string) self::PEARS_PRODUCT_ID,
                    'cart_quantity[]' => '2',
                ],
            ]);
            $this->assertMatchesRegularExpression(
                '/cart_quantity\[\][^>]*value="2"/',
                $updated->getContent(false)
            );

            $this->complete_cod_checkout();

            $this->assertSame(
                $before - 2,
                http_checkout_fixture_sql::products_quantity(self::PEARS_PRODUCT_ID)
            );
        } finally {
            http_checkout_fixture_sql::set_products_quantity(self::PEARS_PRODUCT_ID, $before);
        }
    }

    public function test_zero_quantity_allows_checkout_and_cart_shows_stock_warning(): void {
        http_checkout_fixture_sql::set_pears_quantity_and_allow_checkout(0, 'true');

        $this->login_fixture_customer();

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => (string) self::PEARS_PRODUCT_ID,
            ],
        ]);

        $cart = $this->get_http()->request('GET', '/shopping_cart.php');
        $this->assertSame(200, $cart->getStatusCode());
        $cart_html = $cart->getContent(false);
        $this->assertStringContainsString('cm-sc-stock-notice', $cart_html);
        $this->assertStringContainsString('alert-warning', $cart_html);
        $this->assertStringContainsString('You can buy them anyway', $cart_html);
        $this->assertStringContainsString('fa-times', $cart_html);

        $this->complete_cod_checkout();

        $this->assertSame(-1, http_checkout_fixture_sql::products_quantity(self::PEARS_PRODUCT_ID));
    }

    private function complete_cod_checkout(): void {
        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
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
    }

}
