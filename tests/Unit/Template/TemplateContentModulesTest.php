<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use hooks;
use PhoenixCart\Tests\Support\PhoenixTestCase;
use Template;

final class TemplateContentModulesTest extends PhoenixTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        if (!defined('MODULE_CONTENT_INSTALLED')) {
            define(
                'MODULE_CONTENT_INSTALLED',
                'header/cm_header_menu;footer/cm_footer_text;header/cm_header_breadcrumb'
            );
        }

        $GLOBALS['all_hooks'] = new hooks('shop');
    }

    /**
     * @dataProvider contentModulesProvider
     *
     * @param list<string> $expectedModules
     */
    public function testGetContentModulesFiltersByGroup(string $group, array $expectedModules): void
    {
        $template = new Template(new default_template());

        $this->assertSame($expectedModules, $template->get_content_modules($group));
    }

    public static function contentModulesProvider(): array
    {
        return [
            'header group collects header modules' => [
                'header',
                ['cm_header_menu', 'cm_header_breadcrumb'],
            ],
            'footer group collects footer modules' => [
                'footer',
                ['cm_footer_text'],
            ],
            'unknown group returns empty list' => [
                'body',
                [],
            ],
        ];
    }
}
