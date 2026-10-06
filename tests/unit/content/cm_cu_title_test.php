<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cu_title;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cu_title_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        if (is_file(DIR_FS_CATALOG . 'includes/languages/english/modules/content/contact_us/cm_cu_title.php')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/content/contact_us/cm_cu_title.php';
        }

        $this->define_constants([
            'MODULE_CONTENT_CU_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_CU_TITLE_CONTENT_WIDTH' => 'col-sm-12',
        ]);
    }

    public function test_execute_buffers_module_markup(): void {
        $this->execute_module(cm_cu_title::class);

        $content = $this->buffered_content('contact_us');
        $this->assertStringContainsString('cm-cu-title', $content);
    }

}
