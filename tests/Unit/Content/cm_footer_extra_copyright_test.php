<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Content;

use cm_footer_extra_copyright;
use PhoenixCart\Tests\Support\content_module_test_case;

final class cm_footer_extra_copyright_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_FOOTER_EXTRA_COPYRIGHT_STATUS' => 'True',
            'MODULE_CONTENT_FOOTER_EXTRA_COPYRIGHT_CONTENT_WIDTH' => 'col-sm-6',
            'STORE_NAME' => 'Acme Shop',
            'FOOTER_TEXT_BODY' => '<p>Copyright &copy; %s <a href="%s">%s</a></p>',
        ]);
    }

    public function test_execute_buffers_copyright_into_footer_suffix_group(): void
    {
        $this->execute_module(cm_footer_extra_copyright::class);

        $content = $this->buffered_content('footer_suffix');
        $this->assertStringContainsString((string) date('Y'), $content);
        $this->assertStringContainsString('Acme Shop', $content);
        $this->assertStringContainsString('index.php', $content);
        $this->assertStringContainsString('cm-footer-extra-copyright', $content);
    }
}
