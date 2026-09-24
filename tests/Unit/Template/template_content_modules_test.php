<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use hooks;
use PhoenixCart\Tests\Support\phoenix_test_case;
use Template;
use PHPUnit\Framework\Attributes\DataProvider;

final class template_content_modules_test extends phoenix_test_case
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
     * @param list<string> $expected_modules
     */
    #[DataProvider('content_modules_provider')]
    public function test_get_content_modules_filters_by_group(string $group, array $expected_modules): void
    {
        $template = new Template(new default_template());

        $this->assertSame($expected_modules, $template->get_content_modules($group));
    }

    public static function content_modules_provider(): array
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
