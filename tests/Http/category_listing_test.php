<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Http;

use PhoenixCart\Tests\Support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class category_listing_test extends http_test_case
{
    public function test_fruit_category_lists_sample_oranges(): void
    {
        $response = $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'cPath' => '1',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString('Fruit', $body);
        $this->assertStringContainsString('Citrus Fruit', $body);
    }

    public function test_citrus_subcategory_lists_sample_lemons(): void
    {
        $response = $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'cPath' => '1_4',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString('Citrus Fruit', $body);
        $this->assertStringContainsString('Oranges', $body);
        $this->assertStringContainsString('Lemons', $body);
    }
}
