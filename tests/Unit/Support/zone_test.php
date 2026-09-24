<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use PhoenixCart\Tests\Support\mock_catalog_database;
use PhoenixCart\Tests\Support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Zone;

#[Group('mockdb')]
final class zone_test extends phoenix_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_by_country_returns_seeded_zones(): void
    {
        $zones = [
            ['id' => '1', 'text' => 'North'],
            ['id' => '2', 'text' => 'South'],
        ];

        (new mock_catalog_database([], ['zones' => $zones]))->install_as_global();

        $this->assertSame($zones, Zone::fetch_by_country(223));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_name_reads_from_query_result(): void
    {
        (new mock_catalog_database([], [
            'zones' => [
                ['zone_name' => 'Midlands'],
            ],
        ]))->install_as_global();

        $this->assertSame('Midlands', Zone::fetch_name(5));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_code_reads_from_query_result(): void
    {
        (new mock_catalog_database([], [
            'zones' => [
                ['zone_code' => 'MID'],
            ],
        ]))->install_as_global();

        $this->assertSame('MID', Zone::fetch_code(5, 223, 'XX'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_name_returns_default_when_row_missing(): void
    {
        (new mock_catalog_database([], ['zones' => []]))->install_as_global();

        $this->assertSame('Fallback', Zone::fetch_name(99, null, 'Fallback'));
    }
}
