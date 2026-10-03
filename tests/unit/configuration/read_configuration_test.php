<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\configuration;

use PhoenixCart\Tests\support\configuration_test_helper;
use PhoenixCart\Tests\support\mock_catalog_database;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[Group('mockdb')]
final class read_configuration_test extends phoenix_test_case {

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_read_configuration_defines_keys_from_mock_database(): void {
        $rows = [
            [
                'configuration_key' => 'MODULE_CONTENT_INSTALLED',
                'configuration_value' => 'header/cm_header_menu;footer/cm_footer_text',
            ],
            [
                'configuration_key' => 'TEMPLATE_BLOCK_GROUPS',
                'configuration_value' => 'boxes;header_tags',
            ],
        ];

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));

        $this->assertSame('header/cm_header_menu;footer/cm_footer_text', MODULE_CONTENT_INSTALLED);
        $this->assertSame('boxes;header_tags', TEMPLATE_BLOCK_GROUPS);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_load_skips_constants_already_defined_in_process(): void {
        define('STORE_NAME', 'Acme Shop');

        $rows = [
            [
                'configuration_key' => 'STORE_NAME',
                'configuration_value' => 'Fixture Shop',
            ],
        ];

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));

        $this->assertSame('Acme Shop', STORE_NAME);
    }

}
