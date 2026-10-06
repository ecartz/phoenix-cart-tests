<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_sc_no_products;
use PhoenixCart\Tests\support\content_module_test_case;
use shoppingCart;

final class cm_sc_no_products_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        if (is_file(DIR_FS_CATALOG . 'includes/languages/english/modules/content/shopping_cart/cm_sc_no_products.php')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/content/shopping_cart/cm_sc_no_products.php';
        }

        $this->define_constants([
            'MODULE_CONTENT_SC_NO_PRODUCTS_STATUS' => 'True',
            'MODULE_CONTENT_SC_NO_PRODUCTS_CONTENT_WIDTH' => 'col-sm-12',
        ]);

        $_SESSION['cart'] = new shoppingCart();
    }

    protected function tearDown(): void {
        unset($_SESSION['customer_id'], $_SESSION['cart'], $GLOBALS['product'], $GLOBALS['customer']);
        unset($_GET['products_id'], $_GET['cPath']);
        unset($GLOBALS['keywords'], $GLOBALS['listing_sql'], $GLOBALS['listing_split']);
        unset($GLOBALS['current_category_id'], $GLOBALS['category_tree']);

        parent::tearDown();
    }

    public function test_execute_buffers_module_markup(): void {
        $this->execute_module(cm_sc_no_products::class);

        $content = $this->buffered_content('shopping_cart');
        $this->assertStringContainsString('cm-sc-no-products', $content);
    }

}
