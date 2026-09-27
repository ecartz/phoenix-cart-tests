<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\template;

use default_template;
use PhoenixCart\Tests\support\phoenix_test_case;
use Template;

final class template_map_test extends phoenix_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }
    }

    public function test_map_uses_template_mapping_when_file_exists(): void
    {
        $template = new Template(new default_template());

        $mapped = $template->map('index.php', 'page');

        $this->assertFileExists($mapped);
        $this->assertStringEndsWith(
            'templates/default/includes/pages/index.php',
            str_replace('\\', '/', $mapped)
        );
    }

    public function test_map_falls_back_when_template_returns_null(): void
    {
        $stub = new class {
            public function get_template_mapping_for($file, $type)
            {
                return null;
            }
        };

        $template = new Template($stub);
        $expected = default_template::_get_template_mapping_for('index.php', 'page');

        $this->assertSame($expected, $template->map('index.php', 'page'));
    }

    public function test_map_module_type_builds_tpl_path(): void
    {
        $module_file = DIR_FS_CATALOG . 'includes/modules/content/header/cm_header_menu.php';
        $template = new Template(new default_template());

        $mapped = $template->map($module_file, 'module');

        $this->assertStringEndsWith(
            'includes/modules/content/header/templates/tpl_cm_header_menu.php',
            str_replace('\\', '/', $mapped)
        );
        $this->assertFileExists($mapped);
    }
}
