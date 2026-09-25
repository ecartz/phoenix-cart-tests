<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Http;

use PhoenixCart\Tests\Support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class index_smoke_test extends http_test_case
{
    public function test_homepage_returns_ok_and_lists_sample_catalog(): void
    {
        $response = $this->get_http()->request('GET', '/');

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString('Oranges', $body);
    }
}
