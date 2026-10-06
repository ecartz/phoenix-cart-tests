<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_t_write;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_t_write_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $_SESSION['customer_id'] = 1;
        $this->load_language_file_if_missing('modules/content/testimonials/cm_t_write.php');
    }

    protected function tearDown(): void {
        unset($_SESSION['customer_id']);

        parent::tearDown();
    }

    public function test_execute_buffers_write_testimonial_form_for_logged_in_customer(): void {
        $this->execute_module(cm_t_write::class);

        $content = $this->buffered_content('testimonials');
        $this->assertStringContainsString('cm-t-write', $content);
    }

}
