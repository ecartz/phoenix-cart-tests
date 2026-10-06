<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_sc_order_subtotal;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_sc_order_subtotal_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/shopping_cart/cm_sc_order_subtotal.php',
            'MODULE_CONTENT_SC_ORDER_SUBTOTAL_SUB_TOTAL'
        );
        $this->cart_with_pears();
    }

    protected function tearDown(): void {
        unset($_SESSION['cart']);

        parent::tearDown();
    }

    public function test_execute_buffers_pears_subtotal(): void {
        $this->execute_module(cm_sc_order_subtotal::class);

        $content = $this->buffered_content('shopping_cart');
        $this->assertStringContainsString('cm-sc-order-subtotal', $content);
        $this->assertStringContainsString('Sub-Total:', $content);
        $this->assertStringContainsString('4.99', $content);
    }

}
