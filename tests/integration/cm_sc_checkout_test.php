<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_sc_checkout;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;
use shoppingCart;

#[Group('mysql')]
final class cm_sc_checkout_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $_SESSION['cart'] = new shoppingCart();
        $_SESSION['cart']->add_cart(1, 1);
    }

    protected function tearDown(): void {
        unset($_SESSION['cart']);

        parent::tearDown();
    }

    public function test_execute_buffers_checkout_button_when_cart_has_products(): void {
        $this->load_language_file_if_missing('modules/content/shopping_cart/cm_sc_checkout.php');

        $this->execute_module(cm_sc_checkout::class);

        $content = $this->buffered_content('shopping_cart');
        $this->assertStringContainsString('cm-sc-checkout', $content);
    }

}
