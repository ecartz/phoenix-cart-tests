<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use PhoenixCart\Tests\Support\PhoenixTestCase;
use Template;

final class TemplateMapTest extends PhoenixTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }
    }

    public function testMapUsesTemplateMappingWhenFileExists(): void
    {
        $template = new Template(new default_template());

        $mapped = $template->map('index.php', 'page');

        $this->assertFileExists($mapped);
        $this->assertStringEndsWith(
            'templates/default/includes/pages/index.php',
            str_replace('\\', '/', $mapped)
        );
    }

    public function testMapFallsBackWhenTemplateReturnsNull(): void
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

    public function testMapModuleTypeBuildsTplPath(): void
    {
        $moduleFile = DIR_FS_CATALOG . 'includes/modules/content/header/cm_header_menu.php';
        $template = new Template(new default_template());

        $mapped = $template->map($moduleFile, 'module');

        $this->assertStringEndsWith(
            'includes/modules/content/header/templates/tpl_cm_header_menu.php',
            str_replace('\\', '/', $mapped)
        );
        $this->assertFileExists($mapped);
    }
}
