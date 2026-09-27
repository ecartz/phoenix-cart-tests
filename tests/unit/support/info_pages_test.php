<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use info_pages;
use PhoenixCart\Tests\support\mock_catalog_database;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[Group('mockdb')]
final class info_pages_test extends phoenix_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_requirements_detects_missing_slugs(): void
    {
        (new mock_catalog_database([], [
            'pages' => [
                ['slug' => 'privacy'],
                ['slug' => 'shipping'],
            ],
        ]))->install_as_global();

        $missing = info_pages::requirements(['conditions', 'privacy', 'shipping']);

        $this->assertSame(['conditions'], array_values($missing));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_get_container_matches_lowercase_from_pages(): void
    {
        $rows = [
            [
                'pages_id' => '1',
                'slug' => 'privacy',
                'pages_title' => 'Privacy',
            ],
        ];

        (new mock_catalog_database([], ['pages' => $rows]))->install_as_global();

        $this->assertSame($rows, info_pages::getContainer(['pd.languages_id' => '1']));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_get_element_reads_column_from_query_result(): void
    {
        (new mock_catalog_database([], [
            'pages' => [
                ['pages_text' => 'Privacy body'],
            ],
        ]))->install_as_global();

        $this->assertSame(
            'Privacy body',
            info_pages::getElement(['p.slug' => 'privacy', 'pd.languages_id' => '1'], 'pages_text')
        );
    }
}
