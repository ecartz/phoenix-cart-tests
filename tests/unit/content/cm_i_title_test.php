<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_i_title;
use PhoenixCart\Tests\support\content_module_test_case;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class cm_i_title_test extends content_module_test_case
{
    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_I_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_I_TITLE_CONTENT_WIDTH' => 'col-sm-12 mb-4',
            'MODULE_CONTENT_I_TITLE_PUBLIC_TITLE' => 'Welcome on %s',
            'STORE_NAME' => 'Acme Shop',
        ]);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_buffers_store_welcome_into_index_group(): void {
        $this->execute_module(cm_i_title::class);

        $content = $this->buffered_content('index');
        $this->assertStringContainsString('Welcome on Acme Shop', $content);
        $this->assertStringContainsString('cm-i-title', $content);
    }
}
