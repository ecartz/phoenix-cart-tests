<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_pi_gtin;
use PhoenixCart\Tests\support\content_module_test_case;
use Product;

final class cm_pi_gtin_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        if (is_file(DIR_FS_CATALOG . 'includes/languages/english/modules/content/product_info/cm_pi_gtin.php')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/content/product_info/cm_pi_gtin.php';
        }

        $this->define_constants([
            'MODULE_CONTENT_PRODUCT_INFO_GTIN_STATUS' => 'True',
            'MODULE_CONTENT_PRODUCT_INFO_GTIN_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_PRODUCT_INFO_GTIN_LENGTH' => 13,
        ]);

        $GLOBALS['product'] = new Product(['id' => 1, 'status' => 1]);
        $GLOBALS['product']->set('gtin', '0123456789012');
    }

    protected function tearDown(): void {
        unset($_SESSION['customer_id'], $_SESSION['cart'], $GLOBALS['product'], $GLOBALS['customer']);
        unset($_GET['products_id'], $_GET['cPath']);
        unset($GLOBALS['keywords'], $GLOBALS['listing_sql'], $GLOBALS['listing_split']);
        unset($GLOBALS['current_category_id'], $GLOBALS['category_tree']);

        parent::tearDown();
    }

    public function test_execute_buffers_module_markup(): void {
        $this->execute_module(cm_pi_gtin::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-gtin', $content);
    }

}
