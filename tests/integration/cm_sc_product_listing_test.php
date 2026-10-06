<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_sc_product_listing;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_sc_product_listing_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/shopping_cart/cm_sc_product_listing.php',
            'MODULE_CONTENT_SC_PRODUCT_LISTING_HEADING_PRODUCT'
        );
        $this->define_constants([
            'STOCK_CHECK' => 'true',
        ]);
        $this->cart_with_pears();
    }

    protected function tearDown(): void {
        unset($_SESSION['cart'], $GLOBALS['any_out_of_stock']);

        parent::tearDown();
    }

    public function test_execute_lists_pears_in_the_cart(): void {
        $this->execute_module(cm_sc_product_listing::class);

        $content = $this->buffered_content('shopping_cart');
        $this->assertStringContainsString('cm-sc-product-listing', $content);
        $this->assertStringContainsString('Pears', $content);
    }

}
