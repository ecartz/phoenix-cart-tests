<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_account_set_password;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_account_set_password_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $_SESSION['customer_id'] = 42;
        $GLOBALS['customer'] = new class {
            public function get(string $key): string {
                return '';
            }
        };
        $GLOBALS['Template']->_data['account']['account'] = [
            'title' => 'My Account',
            'sort_order' => 10,
            'links' => [
                'edit' => [
                    'title' => 'Edit',
                    'link' => 'https://shop.example.com/account_edit.php',
                    'icon' => 'fas fa-user',
                ],
                'password' => [
                    'title' => 'Password',
                    'link' => 'https://shop.example.com/account_password.php',
                    'icon' => 'fas fa-cog',
                ],
            ],
        ];
        $this->define_constants([
            'MODULE_CONTENT_ACCOUNT_SET_PASSWORD_STATUS' => 'True',
            'MODULE_CONTENT_ACCOUNT_SET_PASSWORD_ALLOW_PASSWORD' => 'True',
            'MODULE_CONTENT_ACCOUNT_SET_PASSWORD_SET_PASSWORD_LINK_TITLE' => 'Set a Password',
        ]);
    }

    protected function tearDown(): void {
        unset($_SESSION['customer_id'], $GLOBALS['customer']);

        parent::tearDown();
    }

    public function test_execute_inserts_set_password_link_when_password_empty(): void {
        $module = new cm_account_set_password();
        $this->assertTrue($module->isEnabled());

        $this->execute_module(cm_account_set_password::class);
        $this->build_account_page();

        $content = $this->buffered_content('account');
        $this->assertStringContainsString('Set a Password', $content);
        $this->assertStringContainsString('set_password.php', $content);
    }

}
