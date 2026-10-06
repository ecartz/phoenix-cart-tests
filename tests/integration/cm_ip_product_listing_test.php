<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_ip_product_listing;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_ip_product_listing_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/index_products/cm_ip_product_listing.php',
            'MODULE_CONTENT_IP_PRODUCT_LISTING_TITLE'
        );
        $GLOBALS['current_category_id'] = 3;
        unset($_GET['manufacturers_id'], $_GET['filter_id'], $_GET['sort']);
    }

    protected function tearDown(): void {
        unset($GLOBALS['current_category_id'], $_GET['sort']);

        parent::tearDown();
    }

    public function test_execute_lists_pears_in_category(): void {
        $this->execute_module(cm_ip_product_listing::class);

        $content = $this->buffered_content('index_products');
        $this->assertStringContainsString('cm-ip-product-listing', $content);
        $this->assertStringContainsString('Pears', $content);
    }

}
