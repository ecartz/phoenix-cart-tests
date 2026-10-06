<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_sc_no_products;
use PhoenixCart\Tests\support\content_module_test_case;
use shoppingCart;

final class cm_sc_no_products_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $_SESSION['cart'] = new shoppingCart();
        $this->define_constants([
            'MODULE_CONTENT_SC_NO_PRODUCTS_STATUS' => 'True',
            'MODULE_CONTENT_SC_NO_PRODUCTS_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_SC_NO_PRODUCTS_TEXT_CART_EMPTY' => 'Your cart is empty',
            'MODULE_CONTENT_SC_NO_PRODUCTS_BUTTON_CONTINUE' => 'Continue',
        ]);
    }

    protected function tearDown(): void {
        unset($_SESSION['cart']);

        parent::tearDown();
    }

    public function test_execute_buffers_empty_cart_alert_into_shopping_cart_group(): void {
        $this->execute_module(cm_sc_no_products::class);

        $content = $this->buffered_content('shopping_cart');
        $this->assertStringContainsString('cm-sc-no-products', $content);
        $this->assertStringContainsString('Your cart is empty', $content);
        $this->assertStringContainsString('index.php', $content);
    }

}
