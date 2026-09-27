<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cas_title;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cas_title_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_CAS_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_CAS_TITLE_CONTENT_WIDTH' => 'col-sm-12 mb-4',
            'MODULE_CONTENT_CAS_TITLE_PUBLIC_TITLE' => 'Thanks for setting up your Account',
        ]);
    }

    public function test_execute_buffers_mapped_template_into_create_account_success_group(): void
    {
        $this->execute_module(cm_cas_title::class);

        $content = $this->buffered_content('create_account_success');
        $this->assertStringContainsString('Thanks for setting up your Account', $content);
        $this->assertStringContainsString('cm-cas-title', $content);
    }
}
