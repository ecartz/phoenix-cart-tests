<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use breadcrumb;
use cm_header_breadcrumb;
use PhoenixCart\Tests\support\configuration_test_helper;
use PhoenixCart\Tests\support\content_module_test_case;
use PhoenixCart\Tests\support\mock_catalog_database;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[Group('mockdb')]
final class cm_header_breadcrumb_test extends content_module_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_schema_emits_json_ld_without_product(): void
    {
        $this->load_breadcrumb_configuration('Schema');
        $this->with_linker();

        $trail = new breadcrumb();
        $GLOBALS['breadcrumb'] = $trail;
        unset($GLOBALS['cPath_array']);
        unset($_GET['products_id'], $_GET['manufacturers_id']);

        $this->execute_module(cm_header_breadcrumb::class);

        $entries = $trail->trail();
        $this->assertCount(2, $entries);
        $this->assertSame('Home', $entries[0]['title']);
        $this->assertSame(HTTP_SERVER, (string) $entries[0]['link']);
        $this->assertSame('Catalog', $entries[1]['title']);
        $this->assertSame(
            (string) $GLOBALS['Linker']->build('index.php'),
            (string) $entries[1]['link']
        );

        $this->assertTrue($GLOBALS['Template']->has_blocks('footer_scripts'));
        $block = (string) $GLOBALS['Template']->get_blocks('footer_scripts');
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $block);
        $this->assertStringContainsString('"name":"Home"', $block);
        $this->assertStringContainsString('"name":"Catalog"', $block);
        $this->assertFalse($GLOBALS['Template']->has_content('header'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_schema_prepends_product_model_from_mock_query(): void
    {
        $this->load_breadcrumb_configuration('Schema');
        $this->with_linker();

        (new mock_catalog_database([], [
            'products' => [
                ['products_model' => 'SKU-42'],
            ],
        ]))->install_as_global();

        $trail = new breadcrumb();
        $GLOBALS['breadcrumb'] = $trail;
        unset($GLOBALS['cPath_array']);
        $_GET['products_id'] = '42';
        $_SESSION['languages_id'] = 1;

        $this->execute_module(cm_header_breadcrumb::class);

        $titles = array_column($trail->trail(), 'title');
        $this->assertSame(['Home', 'Catalog', 'SKU-42'], $titles);

        $block = (string) $GLOBALS['Template']->get_blocks('footer_scripts');
        $this->assertStringContainsString('"name":"SKU-42"', $block);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_schema_prepends_category_names_from_stub_tree(): void
    {
        $this->load_breadcrumb_configuration('Schema');
        $this->with_linker();

        $GLOBALS['category_tree'] = new class {
            public function get($category_id, string $key): string
            {
                return match ((string) $category_id . ':' . $key) {
                    '1:name' => 'Root',
                    '2:name' => 'Child',
                    default => '',
                };
            }
        };

        $GLOBALS['cPath_array'] = ['1', '2'];
        unset($_GET['products_id'], $_GET['manufacturers_id']);

        $trail = new breadcrumb();
        $GLOBALS['breadcrumb'] = $trail;

        $this->execute_module(cm_header_breadcrumb::class);

        $this->assertSame(
            ['Home', 'Catalog', 'Root', 'Child'],
            array_column($trail->trail(), 'title')
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_schema_prepends_manufacturer_name_from_stub_brand(): void
    {
        $this->load_breadcrumb_configuration('Schema');
        $this->with_linker();

        $GLOBALS['brand'] = new class {
            public function getData(string $key): string
            {
                return $key === 'manufacturers_name' ? 'Acme Corp' : '';
            }
        };

        unset($GLOBALS['cPath_array']);
        unset($_GET['products_id']);
        $_GET['manufacturers_id'] = '9';

        $trail = new breadcrumb();
        $GLOBALS['breadcrumb'] = $trail;

        $this->execute_module(cm_header_breadcrumb::class);

        $this->assertSame(
            ['Home', 'Catalog', 'Acme Corp'],
            array_column($trail->trail(), 'title')
        );
    }

    private function load_breadcrumb_configuration(string $location): void
    {
        $rows = [
            [
                'configuration_key' => 'MODULE_CONTENT_HEADER_BREADCRUMB_STATUS',
                'configuration_value' => 'True',
            ],
            [
                'configuration_key' => 'MODULE_CONTENT_HEADER_BREADCRUMB_CONTENT_WIDTH',
                'configuration_value' => 'col-sm-12',
            ],
            [
                'configuration_key' => 'MODULE_CONTENT_HEADER_BREADCRUMB_SORT_ORDER',
                'configuration_value' => '40',
            ],
            [
                'configuration_key' => 'MODULE_CONTENT_HEADER_BREADCRUMB_LOCATION',
                'configuration_value' => $location,
            ],
            [
                'configuration_key' => 'MODULE_CONTENT_HEADER_BREADCRUMB_PRODUCT_SEO_OVERRIDE',
                'configuration_value' => 'False',
            ],
            [
                'configuration_key' => 'MODULE_CONTENT_HEADER_BREADCRUMB_MANUFACTURER_SEO_OVERRIDE',
                'configuration_value' => 'False',
            ],
            [
                'configuration_key' => 'MODULE_CONTENT_HEADER_BREADCRUMB_CATEGORY_SEO_OVERRIDE',
                'configuration_value' => 'False',
            ],
        ];

        configuration_test_helper::load_from_configuration_rows($rows, new mock_catalog_database($rows));

        $this->define_constants([
            'MODULE_CONTENT_HEADER_BREADCRUMB_TEXT_TITLE' => 'Breadcrumb',
            'MODULE_CONTENT_HEADER_BREADCRUMB_TEXT_DESCRIPTION' => 'Adds a Breadcrumb Trail',
            'MODULE_CONTENT_HEADER_BREADCRUMB_TITLES' => [
                'Home' => null,
                'Catalog' => 'index.php',
            ],
        ]);
    }
}
