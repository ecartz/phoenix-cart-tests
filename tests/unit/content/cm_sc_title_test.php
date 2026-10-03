<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_sc_title;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_sc_title_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_SC_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_SC_TITLE_CONTENT_WIDTH' => 'col-sm-12 mb-4',
            'MODULE_CONTENT_SC_TITLE_PUBLIC_TITLE' => "What's In My Cart?",
        ]);
    }

    public function test_execute_buffers_mapped_template_into_shopping_cart_group(): void {
        $this->execute_module(cm_sc_title::class);

        $content = $this->buffered_content('shopping_cart');
        $this->assertStringContainsString("What's In My Cart?", $content);
        $this->assertStringContainsString('cm-sc-title', $content);
    }

}
