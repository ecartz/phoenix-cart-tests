<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_navbar;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_navbar_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_NAVBAR_FIXED' => 'default',
        ]);
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_NAVBAR_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_NAVBAR_STATUS);

        $this->execute_module(cm_navbar::class);

        $content = $this->buffered_content('navigation');
        $this->assertStringContainsString('cm-navbar', $content);
    }

}
