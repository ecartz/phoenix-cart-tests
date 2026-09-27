<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class homepage_timing_test extends http_test_case
{
    public function test_homepage_responds_within_budget(): void
    {
        $budget = $this->http_budget_seconds();

        $response = $this->get_http()->request('GET', '/');

        $this->assertSame(200, $response->getStatusCode());

        $total_time = (float) ($response->getInfo('total_time') ?? 0.0);
        $this->assertLessThan(
            $budget,
            $total_time,
            sprintf('Homepage took %.3fs; budget is %.1fs', $total_time, $budget)
        );
    }

    private function http_budget_seconds(): float
    {
        $raw = getenv('PHOENIX_HTTP_BUDGET_SECONDS');
        if ($raw !== false && $raw !== '') {
            return (float) $raw;
        }

        return 10.0;
    }
}
