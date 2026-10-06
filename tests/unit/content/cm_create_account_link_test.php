<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_create_account_link;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_create_account_link_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_CREATE_ACCOUNT_LINK_STATUS' => 'True',
            'MODULE_CONTENT_CREATE_ACCOUNT_LINK_CONTENT_WIDTH' => 'col-sm-6',
            'MODULE_CONTENT_LOGIN_TEXT_NEW_CUSTOMER' => 'New Customer',
        ]);
    }

    public function test_execute_buffers_create_account_button_into_login_group(): void {
        $this->execute_module(cm_create_account_link::class);

        $content = $this->buffered_content('login');
        $this->assertStringContainsString('cm-create-account-link', $content);
        $this->assertStringContainsString('New Customer', $content);
        $this->assertStringContainsString('create_account.php', $content);
    }

}
