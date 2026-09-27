<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class href_redirect_test extends http_test_case
{
    #[DataProvider('info_page_redirect_provider')]
    public function test_unpublished_or_missing_info_page_redirects_to_index(string $pages_id): void
    {
        $http = $this->get_http_without_redirects();

        $response = $http->request('GET', '/info.php', [
            'query' => [
                'pages_id' => $pages_id,
            ],
        ]);

        $this->assertSame(302, $response->getStatusCode());

        $location = $response->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString('index.php', $location);
    }

    public static function info_page_redirect_provider(): array
    {
        return [
            'unpublished ssl_check page' => ['4'],
            'missing pages_id' => ['999'],
        ];
    }

    public function test_product_info_without_products_id_redirects_to_index(): void
    {
        $http = $this->get_http_without_redirects();

        $response = $http->request('GET', '/product_info.php');

        $this->assertSame(302, $response->getStatusCode());

        $location = $response->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString('index.php', $location);
    }

    public function test_buy_now_redirects_to_shopping_cart_when_display_cart_enabled(): void
    {
        $http = $this->get_http_without_redirects();
        $http->request('GET', '/');

        $response = $http->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '1',
            ],
        ]);

        $this->assertSame(302, $response->getStatusCode());

        $location = $response->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString('shopping_cart.php', $location);
    }
}
