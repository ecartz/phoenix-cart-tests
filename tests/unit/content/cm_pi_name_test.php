<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_pi_name;
use PhoenixCart\Tests\support\content_module_test_case;
use Product;

final class cm_pi_name_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        if (is_file(DIR_FS_CATALOG . 'includes/languages/english/modules/content/product_info/cm_pi_name.php')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/content/product_info/cm_pi_name.php';
        }

        $this->define_constants([
            'MODULE_CONTENT_PI_NAME_STATUS' => 'True',
            'MODULE_CONTENT_PI_NAME_CONTENT_WIDTH' => 'col-sm-12',
        ]);

        $GLOBALS['product'] = new Product(['id' => 1, 'status' => 1, 'name' => 'Oranges']);
    }

    protected function tearDown(): void {
        unset($_SESSION['customer_id'], $_SESSION['cart'], $GLOBALS['product'], $GLOBALS['customer']);
        unset($_GET['products_id'], $_GET['cPath']);
        unset($GLOBALS['keywords'], $GLOBALS['listing_sql'], $GLOBALS['listing_split']);
        unset($GLOBALS['current_category_id'], $GLOBALS['category_tree']);

        parent::tearDown();
    }

    public function test_execute_buffers_module_markup(): void {
        $this->execute_module(cm_pi_name::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-name', $content);
    }

}
