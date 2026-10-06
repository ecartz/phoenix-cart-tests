<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_t_list;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_t_list_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/testimonials/cm_t_list.php',
            'MODULE_CONTENT_TESTIMONIALS_LIST_NO_TESTIMONIALS'
        );
    }

    public function test_execute_lists_sample_testimonial(): void {
        $this->execute_module(cm_t_list::class);

        $content = $this->buffered_content('testimonials');
        $this->assertStringContainsString('cm-t-list', $content);
        $this->assertStringContainsString('Amazing service', $content);
    }

}
