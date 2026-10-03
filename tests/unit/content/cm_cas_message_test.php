<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cas_message;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cas_message_test extends content_module_test_case
{
    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_CAS_MESSAGE_STATUS' => 'True',
            'MODULE_CONTENT_CAS_MESSAGE_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_CAS_MESSAGE_PUBLIC_TITLE' => '<p>Manage <a href="%2$s">account</a> or <a href="%1$s">contact us</a>.</p>',
        ]);
    }

    public function test_execute_buffers_thank_you_message_into_create_account_success_group(): void {
        $this->execute_module(cm_cas_message::class);

        $content = $this->buffered_content('create_account_success');
        $this->assertStringContainsString('alert-success', $content);
        $this->assertStringContainsString('contact_us.php', $content);
        $this->assertStringContainsString('account.php', $content);
        $this->assertStringContainsString('cm-cas-message', $content);
    }
}
