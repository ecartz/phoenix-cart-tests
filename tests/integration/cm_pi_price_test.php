<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_pi_price;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;
use Product;

#[Group('mysql')]
final class cm_pi_price_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $GLOBALS['product'] = new Product(['id' => 1, 'status' => 1]);
        $this->define_constants([
            'MODULE_CONTENT_PI_PRICE_STATUS' => 'True',
            'MODULE_CONTENT_PI_PRICE_CONTENT_WIDTH' => 'col-sm-5 text-start text-sm-end',
        ]);
        $this->load_language_file_if_missing('modules/content/product_info/cm_pi_price.php');
    }

    protected function tearDown(): void {
        unset($GLOBALS['product'], $GLOBALS['currencies']);

        parent::tearDown();
    }

    public function test_execute_buffers_product_price(): void {
        $this->execute_module(cm_pi_price::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-price', $content);
    }

}
