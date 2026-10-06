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

        $_SESSION['languages_id'] = 1;
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_TESTIMONIALS_LIST_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_TESTIMONIALS_LIST_STATUS);

        $this->execute_module(cm_t_list::class);

        $content = $this->buffered_content('testimonials');
        $this->assertStringContainsString('cm-t-list', $content);
    }

}
