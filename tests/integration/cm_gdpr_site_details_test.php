<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_site_details;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_site_details_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/gdpr/cm_gdpr_site_details.php',
            'MODULE_CONTENT_GDPR_SITE_DETAILS_PUBLIC_TITLE'
        );
        $this->seed_customer();
    }

    protected function tearDown(): void {
        $this->delete_customer();

        parent::tearDown();
    }

    public function test_execute_shows_site_usage_counts(): void {
        $this->execute_module(cm_gdpr_site_details::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-site-details', $content);
        $this->assertStringContainsString('Your Site Details', $content);
        $this->assertStringContainsString('Reviews Written By You', $content);
    }

}
