<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use PhoenixCart\Tests\support\mysql_test_case;
use PHPUnit\Framework\Attributes\Group;
use Product;

#[Group('mysql')]
final class product_fetch_name_test extends mysql_test_case
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION['languages_id'] = 1;
    }

    public function test_fetch_name_returns_sample_oranges(): void
    {
        $this->assertSame('Oranges', Product::fetch_name(1));
    }

    public function test_fetch_name_returns_na_when_product_missing(): void
    {
        $this->assertSame('N/A', Product::fetch_name(999, 1));
    }
}
