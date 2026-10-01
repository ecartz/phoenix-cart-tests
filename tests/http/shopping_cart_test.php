<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class shopping_cart_test extends http_test_case
{
    public function test_buy_now_adds_sample_oranges_and_shows_cart(): void
    {
        // Establish PHP session cookie (required by parse_actions.php).
        $this->get_http()->request('GET', '/');

        $response = $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '1',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringContainsString('shopping_cart.php', $final_url);

        $body = $response->getContent(false);
        $this->assertStringContainsString('cm-sc-product-listing', $body);
        $this->assertStringContainsString('>Oranges</a>', $body);
    }

    public function test_shopping_cart_lists_line_after_buy_now_action(): void
    {
        $this->get_http()->request('GET', '/');

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $cart = $this->get_http()->request('GET', '/shopping_cart.php');

        $this->assertSame(200, $cart->getStatusCode());
        $body = $cart->getContent(false);
        $this->assertStringContainsString('cm-sc-product-listing', $body);
        $this->assertStringContainsString('>Pears</a>', $body);
    }

    public function test_cart_quantity_update_and_line_removal(): void
    {
        $this->get_http()->request('GET', '/');

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '1',
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
                'products_id[]' => '1',
                'cart_quantity[]' => '2',
            ],
        ]);
        $this->assertSame(200, $updated->getStatusCode());
        $updated_body = $updated->getContent(false);
        $this->assertMatchesRegularExpression('/cart_quantity\[\][^>]*value="2"/', $updated_body);

        $formid_after = self::parse_hidden_input($updated_body, 'formid');
        $this->assertNotSame('', $formid_after);

        $removed = $this->get_http()->request('POST', '/shopping_cart.php', [
            'query' => [
                'action' => 'update_product',
            ],
            'body' => [
                'formid' => $formid_after,
                'products_id[]' => '1',
                'cart_delete[]' => '1',
            ],
        ]);
        $removed_body = $removed->getContent(false);
        $this->assertStringNotContainsString('>Oranges</a>', $removed_body);
    }

    public function test_cart_lists_non_download_product_attribute(): void
    {
        http_checkout_fixture_sql::insert_cart_attribute_for_pears();

        try {
            $this->get_http()->request('GET', '/');

            $option_id = http_checkout_fixture_sql::cart_option_id();
            $value_id = http_checkout_fixture_sql::cart_value_id();

            $this->get_http()->request('POST', '/product_info.php', [
                'query' => [
                    'products_id' => '3',
                    'action' => 'add_product',
                ],
                'body' => [
                    'id[' . $option_id . ']' => (string) $value_id,
                ],
            ]);

            $cart = $this->get_http()->request('GET', '/shopping_cart.php');
            $body = $cart->getContent(false);
            $this->assertStringContainsString('HTTP Test Cart Option', $body);
            $this->assertStringContainsString('HTTP Cart Red', $body);
        } finally {
            http_checkout_fixture_sql::remove_cart_attribute_for_pears();
        }
    }
}
