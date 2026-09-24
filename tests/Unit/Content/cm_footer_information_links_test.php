<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Content;

use cm_footer_information_links;
use PhoenixCart\Tests\Support\content_module_test_case;

final class cm_footer_information_links_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_FOOTER_INFORMATION_STATUS' => 'True',
            'MODULE_CONTENT_FOOTER_INFORMATION_CONTENT_WIDTH' => 'col-sm-6',
            'MODULE_CONTENT_FOOTER_INFORMATION_HEADING_TITLE' => 'Information',
            'MODULE_CONTENT_FOOTER_INFORMATION_DATA' => [
                'privacy.php' => 'Privacy &amp; Cookie Policy',
                'conditions.php' => 'Terms &amp; Conditions',
            ],
        ]);
    }

    public function test_execute_buffers_information_links_into_footer_group(): void
    {
        $this->execute_module(cm_footer_information_links::class);

        $content = $this->buffered_content('footer');
        $this->assertStringContainsString('Information', $content);
        $this->assertStringContainsString('Privacy &amp; Cookie Policy', $content);
        $this->assertStringContainsString('privacy.php', $content);
        $this->assertStringContainsString('cm-footer-information-links', $content);
    }
}
