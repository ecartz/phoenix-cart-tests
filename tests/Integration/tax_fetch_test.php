<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Integration;

use PhoenixCart\Tests\Support\mysql_test_case;
use PHPUnit\Framework\Attributes\Group;
use Tax;

#[Group('mysql')]
final class tax_fetch_test extends mysql_test_case
{
    public function test_fetch_returns_florida_rate_for_store_zone(): void
    {
        $this->assertSame('223', (string) STORE_COUNTRY);
        $this->assertSame('18', (string) STORE_ZONE);

        $tax = Tax::fetch(1, 223, 18);

        $this->assertSame(7.0, (float) $tax['rate']);
        $this->assertStringContainsString('FL TAX', $tax['description']);
    }

    public function test_get_caches_fetch_result(): void
    {
        $first = Tax::get(1, 223, 18);
        $second = Tax::get(1, 223, 18);

        $this->assertSame($first, $second);
        $this->assertSame(7.0, (float) $first['rate']);
    }
}
