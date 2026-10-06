<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cu_title;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cu_title_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_CU_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_CU_TITLE_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_CU_TITLE_PUBLIC_TITLE' => 'Contact Us',
        ]);
    }

    public function test_execute_buffers_title_into_contact_us_group(): void {
        $this->execute_module(cm_cu_title::class);

        $content = $this->buffered_content('contact_us');
        $this->assertStringContainsString('cm-cu-title', $content);
        $this->assertStringContainsString('Contact Us', $content);
    }

}
