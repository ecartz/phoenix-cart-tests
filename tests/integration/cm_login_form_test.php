<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_login_form;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_login_form_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
        $this->with_linker();
        $GLOBALS['customer_data'] = new customer_data();
    }

    public function test_execute_buffers_login_form(): void {
        $this->execute_module(cm_login_form::class);

        $content = $this->buffered_content('login');
        $this->assertStringContainsString('cm-login-form', $content);
    }

}
