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

        $GLOBALS['current_category_id'] = 1;
        $_GET['cPath'] = '1';
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_IP_PRODUCT_LISTING_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_IP_PRODUCT_LISTING_STATUS);

        $this->execute_module(cm_ip_product_listing::class);

        $content = $this->buffered_content('index_products');
        $this->assertStringContainsString('cm-ip-product-listing', $content);
    }

}
