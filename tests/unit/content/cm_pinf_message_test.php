<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_pinf_message;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_pinf_message_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_PINF_MESSAGE_STATUS' => 'True',
            'MODULE_CONTENT_PINF_MESSAGE_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_PINF_MESSAGE_PRODUCT_NOT_FOUND' => "D'oh. Product not found!",
        ]);
    }

    public function test_execute_buffers_not_found_alert_into_product_info_not_found_group(): void
    {
        $this->execute_module(cm_pinf_message::class);

        $content = $this->buffered_content('product_info_not_found');
        $this->assertStringContainsString("D'oh. Product not found!", $content);
        $this->assertStringContainsString('alert-danger', $content);
        $this->assertStringContainsString('cm-pinf-message', $content);
    }
}
