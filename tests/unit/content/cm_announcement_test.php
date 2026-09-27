<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_announcement;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_announcement_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_ANNOUNCEMENT_STATUS' => 'True',
            'MODULE_CONTENT_ANNOUNCEMENT_STYLE_BG' => 'text-bg-dark',
            'MODULE_CONTENT_ANNOUNCEMENT_TEXT' => 'Free delivery over $99',
        ]);
    }

    public function test_execute_buffers_mapped_template_into_navigation_group(): void
    {
        $this->execute_module(cm_announcement::class);

        $content = $this->buffered_content('navigation');
        $this->assertStringContainsString('text-bg-dark', $content);
        $this->assertStringContainsString('Free delivery over $99', $content);
    }
}
