<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_pi_gtin;
use PhoenixCart\Tests\support\content_module_test_case;
use Product;

final class cm_pi_gtin_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_PRODUCT_INFO_GTIN_STATUS' => 'True',
            'MODULE_CONTENT_PRODUCT_INFO_GTIN_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_PRODUCT_INFO_GTIN_LENGTH' => '8',
            'MODULE_CONTENT_PRODUCT_INFO_GTIN_PUBLIC_TITLE' => 'GTIN %s',
        ]);
        $GLOBALS['product'] = new Product([
            'id' => 3,
            'status' => 1,
            'gtin' => '00012345678905',
        ]);
    }

    protected function tearDown(): void {
        unset($GLOBALS['product']);

        parent::tearDown();
    }

    public function test_execute_buffers_trimmed_gtin_into_product_info_group(): void {
        $this->execute_module(cm_pi_gtin::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-gtin', $content);
        $this->assertStringContainsString('GTIN 45678905', $content);
    }

}
