<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_account_title;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_account_title_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_ACCOUNT_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_ACCOUNT_TITLE_CONTENT_WIDTH' => 'col-sm-12 mb-4',
            'MODULE_CONTENT_ACCOUNT_TITLE_PUBLIC_TITLE' => 'Account',
        ]);
    }

    public function test_execute_buffers_mapped_template_into_account_group(): void {
        $this->execute_module(cm_account_title::class);

        $content = $this->buffered_content('account');
        $this->assertStringContainsString('Account', $content);
        $this->assertStringContainsString('cm-account-title', $content);
    }

}
