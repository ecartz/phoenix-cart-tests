<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_sc_product_listing;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;
use shoppingCart;

#[Group('mysql')]
final class cm_sc_product_listing_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['cart'] = new shoppingCart();
        $_SESSION['cart']->add_cart(1, 1);
        $this->with_linker();
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_SC_PRODUCT_LISTING_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_SC_PRODUCT_LISTING_STATUS);

        $this->execute_module(cm_sc_product_listing::class);

        $content = $this->buffered_content('shopping_cart');
        $this->assertStringContainsString('cm-sc-product-listing', $content);
    }

}
