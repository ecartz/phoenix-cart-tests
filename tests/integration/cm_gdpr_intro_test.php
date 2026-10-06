<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_intro;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_intro_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language('modules/content/gdpr/cm_gdpr_intro.php', 'MODULE_CONTENT_GDPR_INTRO_PUBLIC_TEXT');
    }

    public function test_execute_buffers_privacy_intro_with_account_link(): void {
        $this->execute_module(cm_gdpr_intro::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-intro', $content);
        $this->assertStringContainsString('account.php', $content);
        $this->assertStringContainsString('Your Data', $content);
    }

}
