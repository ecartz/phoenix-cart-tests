<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_i_modular;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_i_modular_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
    }

    public function test_execute_skips_wrapper_when_no_index_children_are_installed(): void {
        $this->assertTrue(defined('MODULE_CONTENT_I_MODULAR_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_I_MODULAR_STATUS);
        $this->assertSame('', MODULE_CONTENT_I_INSTALLED);

        $this->execute_module(cm_i_modular::class);

        $this->assertSame('', $this->buffered_content('index'));
    }

}
