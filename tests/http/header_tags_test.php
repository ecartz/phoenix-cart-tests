<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class header_tags_test extends http_test_case {

    public function test_product_page_includes_title_and_canonical_link(): void {
        $response = $this->get_http()->request('GET', '/product_info.php', [
            'query' => [
                'products_id' => '1',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertMatchesRegularExpression('/<title>.*Oranges.*<\/title>/i', $body);
        $this->assertMatchesRegularExpression(
            '/<link[^>]+rel=["\']canonical["\'][^>]+href=["\'][^"\']*product_info\.php\?products_id=1/',
            $body
        );
    }

    public function test_special_product_shows_special_and_regular_prices(): void {
        $response = $this->get_http()->request('GET', '/product_info.php', [
            'query' => [
                'products_id' => '1',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('$2.99', $body);
        $this->assertStringContainsString('$9.99', $body);
    }

}
