<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use hooks;
use PhoenixCart\Tests\Support\configuration_test_helper;
use PhoenixCart\Tests\Support\mock_catalog_database;
use PhoenixCart\Tests\Support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Template;

#[Group('mockdb')]
final class template_content_modules_mockdb_test extends phoenix_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_get_content_modules_filters_groups_from_mock_configuration(): void
    {
        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        $rows = [
            [
                'configuration_key' => 'MODULE_CONTENT_INSTALLED',
                'configuration_value' => 'header/cm_header_menu;footer/cm_footer_text;header/cm_header_breadcrumb',
            ],
        ];

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));

        $GLOBALS['all_hooks'] = new hooks('shop');
        $template = new Template(new default_template());

        $this->assertSame(
            ['cm_header_menu', 'cm_header_breadcrumb'],
            $template->get_content_modules('header')
        );
        $this->assertSame(['cm_footer_text'], $template->get_content_modules('footer'));
        $this->assertSame([], $template->get_content_modules('body'));
    }
}
