<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_acceptance_data;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_acceptance_data_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/gdpr/cm_gdpr_acceptance_data.php',
            'MODULE_CONTENT_GDPR_ACCEPTANCE_DATA_PUBLIC_TITLE'
        );
        $this->seed_customer();
        $id = self::FIXTURE_CUSTOMER_ID;
        $this->db()->query(
            "INSERT INTO customers_gdpr (
                customers_id, page, slug, pages_title, pages_text, language, timestamp, date_added
            ) VALUES (
                {$id}, 'privacy', 'privacy', 'Privacy Policy', 'We store fruit preferences.',
                'english', NOW(), NOW()
            )"
        );
    }

    protected function tearDown(): void {
        $this->delete_customer();

        parent::tearDown();
    }

    public function test_execute_shows_accepted_privacy_page(): void {
        $this->execute_module(cm_gdpr_acceptance_data::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-acceptance-data', $content);
        $this->assertStringContainsString('Privacy Policy', $content);
    }

}
