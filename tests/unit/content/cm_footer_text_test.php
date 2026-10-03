<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_footer_text;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_footer_text_test extends content_module_test_case
{
    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_FOOTER_TEXT_STATUS' => 'True',
            'MODULE_CONTENT_FOOTER_TEXT_CONTENT_WIDTH' => 'col-12',
            'MODULE_CONTENT_FOOTER_TEXT_HEADING_TITLE' => 'About our shop',
            'MODULE_CONTENT_FOOTER_TEXT_TEXT' => 'We ship worldwide.',
        ]);
    }

    public function test_execute_buffers_mapped_template_into_footer_group(): void {
        $this->execute_module(cm_footer_text::class);

        $this->assertTrue($GLOBALS['Template']->has_content('footer'));

        $content = $this->buffered_content('footer');
        $this->assertStringContainsString('About our shop', $content);
        $this->assertStringContainsString('We ship worldwide.', $content);
        $this->assertStringContainsString('cm-footer-text', $content);
    }
}
