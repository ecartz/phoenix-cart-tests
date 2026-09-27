<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_footer_extra_icons;
use PhoenixCart\Tests\support\content_module_test_case;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class cm_footer_extra_icons_test extends content_module_test_case
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_uses_raw_text_when_icons_text_defined(): void
    {
        $this->define_constants([
            'MODULE_CONTENT_FOOTER_EXTRA_ICONS_STATUS' => 'True',
            'MODULE_CONTENT_FOOTER_EXTRA_ICONS_CONTENT_WIDTH' => 'col-sm-6',
            'MODULE_CONTENT_FOOTER_EXTRA_ICONS_TEXT' => '<span class="icon-paypal">PayPal</span>',
            'MODULE_CONTENT_FOOTER_EXTRA_ICONS_DISPLAY' => 'fab fa-cc-visa',
        ]);

        $this->execute_module(cm_footer_extra_icons::class);

        $content = $this->buffered_content('footer_suffix');
        $this->assertStringContainsString('icon-paypal', $content);
        $this->assertStringContainsString('PayPal', $content);
        $this->assertStringNotContainsString('fab fa-cc-visa', $content);
        $this->assertStringContainsString('cm-footer-extra-icons', $content);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_renders_icon_classes_from_display_list(): void
    {
        $this->define_constants([
            'MODULE_CONTENT_FOOTER_EXTRA_ICONS_STATUS' => 'True',
            'MODULE_CONTENT_FOOTER_EXTRA_ICONS_CONTENT_WIDTH' => 'col-sm-6',
            'MODULE_CONTENT_FOOTER_EXTRA_ICONS_TEXT' => '',
            'MODULE_CONTENT_FOOTER_EXTRA_ICONS_DISPLAY' => 'fab fa-paypal fa-lg,fab fa-cc-visa fa-lg',
        ]);

        $this->execute_module(cm_footer_extra_icons::class);

        $content = $this->buffered_content('footer_suffix');
        $this->assertStringContainsString('<i class="fab fa-paypal fa-lg"></i>', $content);
        $this->assertStringContainsString('<i class="fab fa-cc-visa fa-lg"></i>', $content);
        $this->assertStringContainsString('cm-footer-extra-icons', $content);
    }
}
