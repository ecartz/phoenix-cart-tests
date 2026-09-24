<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use PhoenixCart\Tests\Support\configuration_test_helper;
use PhoenixCart\Tests\Support\mock_catalog_database;
use PhoenixCart\Tests\Support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Template;

#[Group('mockdb')]
final class template_build_blocks_test extends phoenix_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_build_blocks_loads_constants_via_mock_database(): void
    {
        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        $rows = [
            [
                'configuration_key' => 'TEMPLATE_BLOCK_GROUPS',
                'configuration_value' => '',
            ],
        ];

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));

        $template = new Template(new default_template());
        $template->build_blocks();

        $this->assertTrue(defined('TEMPLATE_BLOCK_GROUPS'));
        $this->assertSame('', TEMPLATE_BLOCK_GROUPS);
    }

    public function test_build_blocks_no_ops_when_block_groups_empty(): void
    {
        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        if (!defined('TEMPLATE_BLOCK_GROUPS')) {
            define('TEMPLATE_BLOCK_GROUPS', '');
        }

        $template = new Template(new default_template());
        $template->build_blocks();

        $this->assertTrue(\Text::is_empty(TEMPLATE_BLOCK_GROUPS));
    }
}
