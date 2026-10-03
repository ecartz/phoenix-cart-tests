<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use ht_robot_noindex;
use PhoenixCart\Tests\support\mysql_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class abstract_module_check_test extends mysql_test_case
{
    public function test_check_finds_status_row_in_configuration(): void {
        $this->assertTrue(defined('MODULE_HEADER_TAGS_ROBOT_NOINDEX_STATUS'));

        if (!defined('MODULE_HEADER_TAGS_ROBOT_NOINDEX_TITLE')) {
            define('MODULE_HEADER_TAGS_ROBOT_NOINDEX_TITLE', 'Robot NoIndex');
        }
        if (!defined('MODULE_HEADER_TAGS_ROBOT_NOINDEX_DESCRIPTION')) {
            define('MODULE_HEADER_TAGS_ROBOT_NOINDEX_DESCRIPTION', 'desc');
        }

        $module = new ht_robot_noindex();

        $this->assertGreaterThan(0, $module->check());
        $this->assertTrue($module->isEnabled());
    }
}
