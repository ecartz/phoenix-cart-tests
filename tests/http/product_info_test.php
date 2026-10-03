<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class product_info_test extends http_test_case
{
    public function test_product_info_page_shows_sample_oranges(): void {
        $response = $this->get_http()->request('GET', '/product_info.php', [
            'query' => [
                'products_id' => '1',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString('Oranges', $body);
        $this->assertStringContainsString('ORA-1', $body);
    }

    public function test_unknown_product_shows_not_found_page_not_oranges(): void {
        $response = $this->get_http()->request('GET', '/product_info.php', [
            'query' => [
                'products_id' => '999999',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringNotContainsString('ORA-1', $body);
        $this->assertStringContainsString('cm-pinf-message', $body);
    }
}
