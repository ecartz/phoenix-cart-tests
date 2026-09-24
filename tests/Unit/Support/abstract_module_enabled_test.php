<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use ht_robot_noindex;
use PhoenixCart\Tests\Support\configuration_test_helper;
use PhoenixCart\Tests\Support\mock_catalog_database;
use PhoenixCart\Tests\Support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[Group('mockdb')]
final class abstract_module_enabled_test extends phoenix_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_is_enabled_true_when_status_constant_true(): void
    {
        $rows = [
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_ROBOT_NOINDEX_STATUS',
                'configuration_value' => 'True',
            ],
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_ROBOT_NOINDEX_PAGES',
                'configuration_value' => 'login.php',
            ],
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_ROBOT_NOINDEX_SORT_ORDER',
                'configuration_value' => '0',
            ],
        ];

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));

        if (!defined('MODULE_HEADER_TAGS_ROBOT_NOINDEX_TITLE')) {
            define('MODULE_HEADER_TAGS_ROBOT_NOINDEX_TITLE', 'Robot NoIndex');
        }
        if (!defined('MODULE_HEADER_TAGS_ROBOT_NOINDEX_DESCRIPTION')) {
            define('MODULE_HEADER_TAGS_ROBOT_NOINDEX_DESCRIPTION', 'desc');
        }

        $module = new ht_robot_noindex();

        $this->assertTrue($module->isEnabled());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_is_enabled_false_when_status_constant_false(): void
    {
        $rows = [
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_ROBOT_NOINDEX_STATUS',
                'configuration_value' => 'False',
            ],
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_ROBOT_NOINDEX_PAGES',
                'configuration_value' => 'login.php',
            ],
        ];

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));

        if (!defined('MODULE_HEADER_TAGS_ROBOT_NOINDEX_TITLE')) {
            define('MODULE_HEADER_TAGS_ROBOT_NOINDEX_TITLE', 'Robot NoIndex');
        }

        $module = new ht_robot_noindex();

        $this->assertFalse($module->isEnabled());
    }
}
