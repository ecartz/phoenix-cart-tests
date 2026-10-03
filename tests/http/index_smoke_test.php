<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class index_smoke_test extends http_test_case
{
    public function test_homepage_returns_ok_and_lists_sample_catalog(): void {
        $response = $this->get_http()->request('GET', '/');

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString('Our Farm', $body);
        $this->assertStringContainsString('Strawberries Coming Soon', $body);
    }
}
