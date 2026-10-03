<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_footer_contact_us;
use PhoenixCart\Tests\support\content_module_test_case;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class cm_footer_contact_us_test extends content_module_test_case
{
    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_FOOTER_CONTACT_US_STATUS' => 'True',
            'MODULE_CONTENT_FOOTER_CONTACT_US_CONTENT_WIDTH' => 'col-sm-6',
            'MODULE_CONTENT_FOOTER_CONTACT_US_HEADING_TITLE' => 'How To Contact Us',
            'MODULE_CONTENT_FOOTER_CONTACT_US_EMAIL_LINK' => 'Contact Us',
            'MODULE_CONTENT_FOOTER_CONTACT_US_PHONE' => 'Phone: ',
            'MODULE_CONTENT_FOOTER_CONTACT_US_EMAIL' => 'Email: ',
            'MODULE_CONTENT_FOOTER_CONTACT_US_TAX_ID' => 'Tax ID: %s',
            'STORE_NAME' => 'Acme Shop',
            'STORE_ADDRESS' => "1 Main St\nTown",
            'STORE_PHONE' => '555-0100',
            'STORE_OWNER_EMAIL_ADDRESS' => 'shop@example.com',
            'STORE_TAX_ID' => '',
        ]);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_buffers_contact_block_into_footer_group(): void {
        $this->execute_module(cm_footer_contact_us::class);

        $content = $this->buffered_content('footer');
        $this->assertStringContainsString('Acme Shop', $content);
        $this->assertStringContainsString('How To Contact Us', $content);
        $this->assertStringContainsString('contact_us.php', $content);
        $this->assertStringContainsString('cm-footer-contact-us', $content);
        $this->assertStringNotContainsString('Tax ID:', $content);
    }
}
