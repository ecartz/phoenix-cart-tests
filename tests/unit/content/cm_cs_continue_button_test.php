<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cs_continue_button;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cs_continue_button_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_CS_CONTINUE_BUTTON_STATUS' => 'True',
            'MODULE_CONTENT_CS_CONTINUE_BUTTON_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_CS_CONTINUE_BUTTON_TEXT' => 'Continue',
        ]);
    }

    public function test_execute_buffers_continue_button_into_checkout_success_group(): void {
        $this->execute_module(cm_cs_continue_button::class);

        $content = $this->buffered_content('checkout_success');
        $this->assertStringContainsString('cm-cs-continue-button', $content);
        $this->assertStringContainsString('Continue', $content);
    }

}
