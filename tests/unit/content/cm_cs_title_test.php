<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cs_title;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cs_title_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_CHECKOUT_SUCCESS_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_TITLE_CONTENT_WIDTH' => 'col-sm-12 mb-4',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_TITLE_PUBLIC_TITLE' => 'Your Order is Complete',
        ]);
    }

    public function test_execute_buffers_mapped_template_into_checkout_success_group(): void
    {
        $this->execute_module(cm_cs_title::class);

        $content = $this->buffered_content('checkout_success');
        $this->assertStringContainsString('Your Order is Complete', $content);
        $this->assertStringContainsString('cm-cs-title', $content);
    }
}
