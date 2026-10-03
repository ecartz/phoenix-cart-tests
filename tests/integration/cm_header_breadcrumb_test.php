<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use breadcrumb;
use cm_header_breadcrumb;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_header_breadcrumb_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
        $this->with_linker();

        if (!defined('MODULE_CONTENT_HEADER_BREADCRUMB_TITLES')) {
            require DIR_FS_CATALOG
                . 'includes/languages/english/modules/content/header/cm_header_breadcrumb.php';
        }

        $GLOBALS['breadcrumb'] = new breadcrumb();
        unset($GLOBALS['cPath_array'], $GLOBALS['category_tree'], $GLOBALS['brand']);
        unset($_GET['manufacturers_id']);
        $_GET['products_id'] = '1';
    }

    protected function tearDown(): void {
        unset($_GET['products_id'], $GLOBALS['breadcrumb']);

        parent::tearDown();
    }

    public function test_execute_schema_prepends_sample_product_model_from_database(): void {
        $this->assertTrue(defined('MODULE_CONTENT_HEADER_BREADCRUMB_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_HEADER_BREADCRUMB_STATUS);
        $this->assertSame('Schema', MODULE_CONTENT_HEADER_BREADCRUMB_LOCATION);

        $row = $this->db()->query(
            'SELECT p.products_model FROM products p WHERE p.products_id = 1'
        )->fetch_assoc();
        $this->assertSame('ORA-1', $row['products_model'] ?? null);

        $this->execute_module(cm_header_breadcrumb::class);

        $titles = array_column($GLOBALS['breadcrumb']->trail(), 'title');
        $this->assertContains('ORA-1', $titles);
        $this->assertContains('Catalog', $titles);
        $this->assertGreaterThanOrEqual(3, count($titles));

        $block = (string) $GLOBALS['Template']->get_blocks('footer_scripts');
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $block);
        $this->assertStringContainsString('"name":"ORA-1"', $block);
    }

}
