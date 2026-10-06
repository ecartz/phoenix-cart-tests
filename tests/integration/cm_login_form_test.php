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

        $this->prepare_storefront();
        $GLOBALS['customer_data'] = new \customer_data();
        $this->load_language_file_if_missing('modules/content/login/cm_login_form.php');
        $this->load_language_file_if_missing('modules/customer_data/cd_email_address.php');
        $this->load_language_file_if_missing('modules/customer_data/cd_password.php');
    }

    public function test_execute_buffers_login_form(): void {
        $this->execute_module(cm_login_form::class);

        $content = $this->buffered_content('login');
        $this->assertStringContainsString('cm-login-form', $content);
    }

}
