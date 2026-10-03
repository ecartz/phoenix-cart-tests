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
        $this->assertStringContainsString('rel="canonical"', $body);
        $this->assertStringContainsString('product_info.php', $body);
    }

    public function test_homepage_includes_title_and_canonical_link(): void {
        $response = $this->get_http()->request('GET', '/');
        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertMatchesRegularExpression('/<title>.*<\/title>/i', $body);
        $this->assertStringContainsString('rel="canonical"', $body);
        $this->assertStringContainsString('index.php', $body);
    }

}
