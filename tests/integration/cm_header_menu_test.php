<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_header_menu;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_header_menu_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
    }

    public function test_execute_buffers_horizontal_menu(): void {
        $this->assertTrue(defined('MODULE_CONTENT_HEADER_MENU_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_HEADER_MENU_STATUS);

        $this->execute_module(cm_header_menu::class);

        $content = $this->buffered_content('header');
        $this->assertStringContainsString('cm-header-menu', $content);
    }

}
