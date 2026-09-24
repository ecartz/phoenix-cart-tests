<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Content;

use cm_header_messagestack;
use messageStack;
use PhoenixCart\Tests\Support\content_module_test_case;

final class cm_header_messagestack_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_HEADER_MESSAGESTACK_STATUS' => 'True',
            'MODULE_CONTENT_HEADER_MESSAGESTACK_CONTENT_WIDTH' => 'col-sm-12',
            'IMAGE_BUTTON_CLOSE' => 'Close',
        ]);
    }

    public function test_execute_buffers_header_messages_when_stack_has_entries(): void
    {
        $stack = new messageStack();
        $stack->add('header', 'Checkout notice', 'warning');
        $GLOBALS['messageStack'] = $stack;

        $this->execute_module(cm_header_messagestack::class);

        $content = $this->buffered_content('header');
        $this->assertStringContainsString('Checkout notice', $content);
        $this->assertStringContainsString('alert-warning', $content);
        $this->assertStringContainsString('cm-header-messagestack', $content);
    }

    public function test_execute_skips_buffering_when_header_stack_empty(): void
    {
        $GLOBALS['messageStack'] = new messageStack();

        $this->execute_module(cm_header_messagestack::class);

        $this->assertFalse($GLOBALS['Template']->has_content('header'));
        $this->assertSame('', $this->buffered_content('header'));
    }
}
