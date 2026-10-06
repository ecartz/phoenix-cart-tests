<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_pi_name;
use PhoenixCart\Tests\support\content_module_test_case;
use Product;

final class cm_pi_name_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_PI_NAME_STATUS' => 'True',
            'MODULE_CONTENT_PI_NAME_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_PI_NAME_DISPLAY_NAME' => '%s',
        ]);
        $GLOBALS['product'] = new Product([
            'id' => 3,
            'status' => 1,
            'name' => 'Pears',
        ]);
    }

    protected function tearDown(): void {
        unset($GLOBALS['product']);

        parent::tearDown();
    }

    public function test_execute_buffers_product_name_into_product_info_group(): void {
        $this->execute_module(cm_pi_name::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-name', $content);
        $this->assertStringContainsString('Pears', $content);
    }

}
