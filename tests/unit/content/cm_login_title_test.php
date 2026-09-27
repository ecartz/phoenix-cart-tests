<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_login_title;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_login_title_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_LOGIN_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_LOGIN_TITLE_CONTENT_WIDTH' => 'col-sm-12 mb-4',
            'MODULE_CONTENT_LOGIN_TITLE_PUBLIC_TITLE' => 'Welcome, Please Sign In',
        ]);
    }

    public function test_execute_buffers_mapped_template_into_login_group(): void
    {
        $this->execute_module(cm_login_title::class);

        $content = $this->buffered_content('login');
        $this->assertStringContainsString('Welcome, Please Sign In', $content);
        $this->assertStringContainsString('cm-login-title', $content);
    }
}
