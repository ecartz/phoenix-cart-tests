<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_cu_modular;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_cu_modular_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_CU_MODULAR_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_CU_MODULAR_STATUS);

        $this->execute_module(cm_cu_modular::class);

        $content = $this->buffered_content('contact_us');
        $this->assertStringContainsString('cm-cu-modular', $content);
    }

}
