<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_sc_order_subtotal;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;
use shoppingCart;

#[Group('mysql')]
final class cm_sc_order_subtotal_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
        $_SESSION['cart'] = new shoppingCart();
        $_SESSION['cart']->add_cart(1, 1);
        $GLOBALS['currencies'] = new currencies();
    }

    protected function tearDown(): void {
        unset($_SESSION['cart'], $GLOBALS['currencies']);

        parent::tearDown();
    }

    public function test_execute_buffers_subtotal_when_cart_has_products(): void {
        $this->execute_module(cm_sc_order_subtotal::class);

        $content = $this->buffered_content('shopping_cart');
        $this->assertStringContainsString('cm-sc-order-subtotal', $content);
    }

}
