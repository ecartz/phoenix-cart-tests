<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_cart;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_cart_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language('modules/content/gdpr/cm_gdpr_cart.php', 'MODULE_CONTENT_GDPR_CART_PUBLIC_TITLE');
        $GLOBALS['port_my_data'] = [];
        $this->cart_with_pears();
    }

    protected function tearDown(): void {
        unset($_SESSION['cart'], $GLOBALS['port_my_data']);

        parent::tearDown();
    }

    public function test_execute_lists_pears_in_the_cart_export(): void {
        $this->execute_module(cm_gdpr_cart::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-cart', $content);
        $this->assertStringContainsString('Pears', $content);
    }

}
