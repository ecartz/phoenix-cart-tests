<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Content;

use cm_info_title;
use PhoenixCart\Tests\Support\content_module_test_case;

final class cm_info_title_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_INFO_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_INFO_TITLE_CONTENT_WIDTH' => 'col-12 mb-4',
        ]);

        $GLOBALS['page'] = [
            'pages_title' => 'Shipping Information',
            'pages_text' => 'We ship worldwide.',
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['page']);

        parent::tearDown();
    }

    public function test_execute_buffers_page_title_into_info_group(): void
    {
        $this->execute_module(cm_info_title::class);

        $content = $this->buffered_content('info');
        $this->assertStringContainsString('Shipping Information', $content);
        $this->assertStringContainsString('cm-info-title', $content);
    }
}
