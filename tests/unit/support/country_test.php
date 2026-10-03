<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use Country;
use PhoenixCart\Tests\support\mock_catalog_database;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[Group('mockdb')]
final class country_test extends phoenix_test_case {

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_all_returns_seeded_countries(): void {
        $countries = [
            ['countries_id' => '1', 'countries_name' => 'Atlantis'],
            ['countries_id' => '2', 'countries_name' => 'Zealand'],
        ];

        (new mock_catalog_database([], ['countries' => $countries]))->install_as_global();

        $this->assertSame($countries, Country::fetch_all());
        $this->assertSame($countries, Country::fetch_all());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_options_returns_id_text_pairs(): void {
        $options = [
            ['id' => '10', 'text' => 'Canada'],
            ['id' => '20', 'text' => 'Mexico'],
        ];

        (new mock_catalog_database([], ['countries' => $options]))->install_as_global();

        $this->assertSame($options, Country::fetch_options());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_returns_single_country_row(): void {
        $row = [
            'countries_name' => 'Ruritania',
            'countries_iso_code_2' => 'RR',
            'countries_iso_code_3' => 'RUR',
        ];

        (new mock_catalog_database([], ['countries' => [$row]]))->install_as_global();

        $this->assertSame($row, Country::fetch(99));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_name_reads_from_query_result(): void {
        (new mock_catalog_database([], [
            'countries' => [
                ['countries_name' => 'Narnia'],
            ],
        ]))->install_as_global();

        $this->assertSame('Narnia', Country::fetch_name(7));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_fetch_id_from_iso_returns_countries_id(): void {
        (new mock_catalog_database([], [
            'countries' => [
                ['countries_id' => '223'],
            ],
        ]))->install_as_global();

        $this->assertSame('223', Country::fetch_id_from_iso('US'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_match_classification_uses_store_country(): void {
        if (!defined('STORE_COUNTRY')) {
            define('STORE_COUNTRY', '223');
        }

        $this->assertTrue(Country::match_classification('national', '223'));
        $this->assertFalse(Country::match_classification('national', '38'));
        $this->assertTrue(Country::match_classification('international', '38'));
        $this->assertTrue(Country::match_classification('both', '1'));
    }

}
