<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\template;

use default_template;
use hooks;
use Linker;
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
final class template_build_blocks_modules_test extends phoenix_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_build_blocks_executes_table_click_jquery(): void
    {
        $this->prepare_request_page('checkout_payment.php');

        $rows = $this->header_tags_rows('ht_table_click_jquery.php', [
            'MODULE_HEADER_TAGS_TABLE_CLICK_JQUERY_STATUS' => 'True',
            'MODULE_HEADER_TAGS_TABLE_CLICK_JQUERY_PAGES' => 'checkout_payment.php',
            'MODULE_HEADER_TAGS_TABLE_CLICK_JQUERY_TR_BACKGROUND' => 'table-success',
            'MODULE_HEADER_TAGS_TABLE_CLICK_JQUERY_SORT_ORDER' => '0',
        ]);

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));
        $this->define_if_missing('MODULE_HEADER_TAGS_TABLE_CLICK_JQUERY_TITLE', 'Table Click');
        $this->define_if_missing('BOOTSTRAP_CONTENT', 8);

        $template = new Template(new default_template());
        $GLOBALS['Template'] = $template;
        $template->build_blocks();

        $this->assertTrue($template->has_blocks('footer_scripts'));
        $this->assertStringContainsString('tr.table-selection', (string) $template->get_blocks('footer_scripts'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_build_blocks_executes_canonical_for_index(): void
    {
        $this->prepare_request_page('index.php');
        $this->with_linker_and_hooks();

        $rows = $this->header_tags_rows('ht_canonical.php', [
            'MODULE_HEADER_TAGS_CANONICAL_STATUS' => 'True',
            'MODULE_HEADER_TAGS_CANONICAL_SORT_ORDER' => '0',
        ]);

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));
        $this->define_if_missing('MODULE_HEADER_TAGS_CANONICAL_TITLE', 'Canonical');
        $this->define_if_missing('BOOTSTRAP_CONTENT', 8);

        $template = new Template(new default_template());
        $GLOBALS['Template'] = $template;
        $template->build_blocks();

        $this->assertTrue($template->has_blocks('header_tags'));
        $this->assertStringContainsString(
            'rel="canonical"',
            (string) $template->get_blocks('header_tags')
        );
        $this->assertStringContainsString('index.php', (string) $template->get_blocks('header_tags'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_build_blocks_executes_pages_seo_with_stub_page(): void
    {
        $this->prepare_request_page('info.php');
        $this->with_linker_and_hooks();

        $rows = $this->header_tags_rows('ht_pages_seo.php', [
            'MODULE_HEADER_TAGS_PAGES_SEO_STATUS' => 'True',
            'MODULE_HEADER_TAGS_PAGES_SEO_SORT_ORDER' => '0',
        ]);

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));
        $this->define_if_missing('MODULE_HEADER_TAGS_PAGES_SEO_TITLE', 'SEO Pages');
        $this->define_if_missing('MODULE_HEADER_TAGS_PAGES_SEO_DESCRIPTION', 'desc');
        $this->define_if_missing('MODULE_HEADER_TAGS_PAGES_SEO_HELPER', 'helper');
        $this->define_if_missing('MODULE_HEADER_TAGS_PAGES_SEO_SEPARATOR', ' | ');
        $this->define_if_missing('BOOTSTRAP_CONTENT', 8);

        $template = new Template(new default_template());
        $template->set_title('Shop');
        $GLOBALS['Template'] = $template;
        $GLOBALS['page'] = [
            'pages_title' => 'Privacy',
            'pages_seo_description' => 'Privacy meta',
        ];

        $template->build_blocks();

        $this->assertSame('Privacy | Shop', $template->get_title());
        $this->assertStringContainsString(
            'name="description" content="Privacy meta"',
            (string) $template->get_blocks('header_tags')
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_build_blocks_executes_category_title_with_stub_tree(): void
    {
        $this->prepare_request_page('index.php');
        $this->with_linker_and_hooks();

        $rows = $this->header_tags_rows('ht_category_title.php', [
            'MODULE_HEADER_TAGS_CATEGORY_TITLE_STATUS' => 'True',
            'MODULE_HEADER_TAGS_CATEGORY_TITLE_SORT_ORDER' => '0',
            'MODULE_HEADER_TAGS_CATEGORY_TITLE_SEO_TITLE_OVERRIDE' => 'False',
        ]);

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));
        $this->define_if_missing('MODULE_HEADER_TAGS_CATEGORY_TITLE_TITLE', 'Category Title');
        $this->define_if_missing('MODULE_HEADER_TAGS_CATEGORY_TITLE_DESCRIPTION', 'desc');
        $this->define_if_missing('MODULE_HEADER_TAGS_CATEGORY_SEO_SEPARATOR', ' | ');
        $this->define_if_missing('BOOTSTRAP_CONTENT', 8);

        $GLOBALS['current_category_id'] = 5;
        $GLOBALS['category_tree'] = new class {
            public function get($id, string $key): string
            {
                return $key === 'name' ? 'Gadgets' : '';
            }
        };

        $template = new Template(new default_template());
        $template->set_title('Shop');
        $GLOBALS['Template'] = $template;
        $template->build_blocks();

        $this->assertSame('Gadgets | Shop', $template->get_title());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_build_blocks_executes_bm_home_box(): void
    {
        $this->with_linker_and_hooks();

        $rows = [
            [
                'configuration_key' => 'TEMPLATE_BLOCK_GROUPS',
                'configuration_value' => 'boxes',
            ],
            [
                'configuration_key' => 'MODULE_BOXES_INSTALLED',
                'configuration_value' => 'bm_home.php',
            ],
            [
                'configuration_key' => 'MODULE_BOXES_HOME_STATUS',
                'configuration_value' => 'True',
            ],
            [
                'configuration_key' => 'MODULE_BOXES_HOME_CONTENT_PLACEMENT',
                'configuration_value' => 'Left Column',
            ],
            [
                'configuration_key' => 'MODULE_BOXES_HOME_SORT_ORDER',
                'configuration_value' => '100',
            ],
        ];

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));
        $this->define_if_missing('MODULE_BOXES_HOME_TITLE', 'Home');
        $this->define_if_missing('MODULE_BOXES_HOME_DESCRIPTION', 'Show Home Link');
        $this->define_if_missing('MODULE_BOXES_HOME_BOX_TITLE', 'Home');
        $this->define_if_missing('BOOTSTRAP_CONTENT', 8);

        $template = new Template(new default_template());
        $GLOBALS['Template'] = $template;

        $previous = getcwd();
        chdir(DIR_FS_CATALOG);
        try {
            $template->build_blocks();
        } finally {
            chdir($previous);
        }

        $this->assertTrue($template->has_blocks('boxes_column_left'));
        $this->assertStringContainsString('bm-home', (string) $template->get_blocks('boxes_column_left'));
        $this->assertStringContainsString('Home', (string) $template->get_blocks('boxes_column_left'));
    }

    /**
     * @param array<string, string> $module_constants
     *
     * @return list<array{configuration_key: string, configuration_value: string}>
     */
    private function header_tags_rows(string $installed, array $module_constants): array
    {
        $rows = [
            [
                'configuration_key' => 'TEMPLATE_BLOCK_GROUPS',
                'configuration_value' => 'header_tags',
            ],
            [
                'configuration_key' => 'MODULE_HEADER_TAGS_INSTALLED',
                'configuration_value' => $installed,
            ],
        ];

        foreach ($module_constants as $key => $value) {
            $rows[] = [
                'configuration_key' => $key,
                'configuration_value' => $value,
            ];
        }

        return $rows;
    }

    private function with_linker_and_hooks(): void
    {
        $this->define_if_missing('HTTP_SERVER', 'https://shop.example.com');
        $this->define_if_missing('DIR_WS_CATALOG', '/');
        $this->define_if_missing('SESSION_FORCE_COOKIE_USE', 'False');

        $_SERVER['SCRIPT_NAME'] ??= '/index.php';
        $GLOBALS['Linker'] = new Linker('https://shop.example.com/');
        $GLOBALS['hooks'] = new hooks('shop');
        $GLOBALS['all_hooks'] = $GLOBALS['hooks'];
    }

    private function prepare_request_page(string $page): void
    {
        $this->define_if_missing('DIR_WS_CATALOG', '/');
        $_SERVER['SCRIPT_NAME'] = '/' . $page;

        $reflection = new ReflectionClass(Request::class);
        $property = $reflection->getProperty('page');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    private function define_if_missing(string $name, mixed $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }
}
