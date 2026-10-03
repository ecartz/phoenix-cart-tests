<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_forgot_password;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_forgot_password_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_FORGOT_PASSWORD_STATUS' => 'True',
            'MODULE_CONTENT_FORGOT_PASSWORD_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_FORGOT_PASSWORD_INTRO_TEXT' => 'Did you forget your Password?  No problem!',
        ]);
    }

    public function test_execute_buffers_button_link_into_login_group(): void {
        $this->execute_module(cm_forgot_password::class);

        $content = $this->buffered_content('login');
        $this->assertStringContainsString('Did you forget your Password?', $content);
        $this->assertStringContainsString('password_forgotten.php', $content);
        $this->assertStringContainsString('cm-forgot-password', $content);
    }

}
