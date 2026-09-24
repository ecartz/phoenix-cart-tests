<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use PhoenixCart\Tests\Support\mock_catalog_database;
use PhoenixCart\Tests\Support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tax;

#[Group('mockdb')]
final class tax_test extends phoenix_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_classes_returns_seeded_tax_classes(): void
    {
        $classes = [
            ['id' => '1', 'text' => 'Taxable Goods'],
            ['id' => '2', 'text' => 'Shipping'],
        ];

        (new mock_catalog_database([], ['tax_class' => $classes]))->install_as_global();

        $this->assertSame($classes, Tax::fetch_classes());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_get_class_title_reads_from_query_result(): void
    {
        (new mock_catalog_database([], [
            'tax_class' => [
                ['tax_class_title' => 'Standard'],
            ],
        ]))->install_as_global();

        $this->assertSame('Standard', Tax::get_class_title(3));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_get_class_title_returns_none_for_zero(): void
    {
        if (!defined('TEXT_NONE')) {
            define('TEXT_NONE', 'None');
        }

        $this->assertSame('None', Tax::get_class_title('0'));
    }
}
