<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\template;

use default_template;
use PhoenixCart\Tests\support\configuration_test_helper;
use PhoenixCart\Tests\support\mock_catalog_database;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionClass;
use Request;
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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_build_blocks_executes_enabled_header_tags_module(): void
    {
        $this->prepare_request_page('login.php');

        $rows = [
            [
                'configuration_key' => 'TEMPLATE_BLOCK_GROUPS',
                'configuration_value' => 'header_tags',
            ],
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_INSTALLED',
                'configuration_value' => 'ht_robot_noindex.php',
            ],
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_ROBOT_NOINDEX_STATUS',
                'configuration_value' => 'True',
            ],
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_ROBOT_NOINDEX_PAGES',
                'configuration_value' => 'login.php;account.php',
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
            define('MODULE_HEADER_TAGS_ROBOT_NOINDEX_DESCRIPTION', 'Add robot noindex tags');
        }
        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        $template = new Template(new default_template());
        $GLOBALS['Template'] = $template;
        $template->build_blocks();

        $this->assertTrue($template->has_blocks('header_tags'));
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex,follow">',
            (string) $template->get_blocks('header_tags')
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_build_blocks_skips_disabled_header_tags_module(): void
    {
        $this->prepare_request_page('login.php');

        $rows = [
            [
                'configuration_key' => 'TEMPLATE_BLOCK_GROUPS',
                'configuration_value' => 'header_tags',
            ],
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_INSTALLED',
                'configuration_value' => 'ht_robot_noindex.php',
            ],
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
        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        $template = new Template(new default_template());
        $GLOBALS['Template'] = $template;
        $template->build_blocks();

        $this->assertFalse($template->has_blocks('header_tags'));
    }

    private function prepare_request_page(string $page): void
    {
        if (!defined('DIR_WS_CATALOG')) {
            define('DIR_WS_CATALOG', '/');
        }

        $_SERVER['SCRIPT_NAME'] = '/' . $page;

        $reflection = new ReflectionClass(Request::class);
        $property = $reflection->getProperty('page');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
