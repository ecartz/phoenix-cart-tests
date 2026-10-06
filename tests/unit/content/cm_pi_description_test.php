<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_pi_description;
use PhoenixCart\Tests\support\content_module_test_case;
use Product;

final class cm_pi_description_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_PI_DESCRIPTION_STATUS' => 'True',
            'MODULE_CONTENT_PI_DESCRIPTION_CONTENT_WIDTH' => 'col-sm-12',
        ]);
        $GLOBALS['product'] = new Product([
            'id' => 3,
            'status' => 1,
            'description' => 'Pears are soft and sweet.',
        ]);
    }

    protected function tearDown(): void {
        unset($GLOBALS['product']);

        parent::tearDown();
    }

    public function test_execute_buffers_product_description_into_product_info_group(): void {
        $this->execute_module(cm_pi_description::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-description', $content);
        $this->assertStringContainsString('Pears are soft and sweet.', $content);
    }

}
