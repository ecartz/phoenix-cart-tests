<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use PhoenixCart\Tests\support\mock_catalog_database;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Product;

#[Group('mockdb')]
final class product_test extends phoenix_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_name_returns_products_name(): void {
        $_SESSION['languages_id'] = 1;

        (new mock_catalog_database([], [
            'products_description' => [
                ['products_name' => 'Widget'],
            ],
        ]))->install_as_global();

        $this->assertSame('Widget', Product::fetch_name(42));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_name_returns_na_when_missing(): void {
        $_SESSION['languages_id'] = 1;

        (new mock_catalog_database([], [
            'products_description' => [],
        ]))->install_as_global();

        $this->assertSame('N/A', Product::fetch_name(99, 1));
    }
}
