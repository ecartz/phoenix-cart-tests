<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Http;

use PhoenixCart\Tests\Support\http_test_case;
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
}
