<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_t_title;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_t_title_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_TESTIMONIALS_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_TESTIMONIALS_TITLE_CONTENT_WIDTH' => 'col-sm-12 mb-4',
            'MODULE_CONTENT_TESTIMONIALS_TITLE_PUBLIC_TITLE' => 'Customer Testimonials',
        ]);
    }

    public function test_execute_buffers_mapped_template_into_testimonials_group(): void {
        $this->execute_module(cm_t_title::class);

        $content = $this->buffered_content('testimonials');
        $this->assertStringContainsString('Customer Testimonials', $content);
        $this->assertStringContainsString('cm-t-title', $content);
    }

}
