<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_info_text;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_info_text_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_INFO_TEXT_STATUS' => 'True',
            'MODULE_CONTENT_INFO_TEXT_CONTENT_WIDTH' => 'col-12',
        ]);

        $GLOBALS['page'] = [
            'pages_title' => 'Shipping Information',
            'pages_text' => 'We ship worldwide within 5 days.',
        ];
    }

    protected function tearDown(): void {
        unset($GLOBALS['page']);

        parent::tearDown();
    }

    public function test_execute_buffers_page_text_into_info_group(): void {
        $this->execute_module(cm_info_text::class);

        $content = $this->buffered_content('info');
        $this->assertStringContainsString('We ship worldwide within 5 days.', $content);
        $this->assertStringContainsString('cm-info-text', $content);
    }

}
