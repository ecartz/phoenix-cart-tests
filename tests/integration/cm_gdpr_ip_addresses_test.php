<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_ip_addresses;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_ip_addresses_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language('modules/content/gdpr/cm_gdpr_ip_addresses.php', 'MODULE_CONTENT_GDPR_IP_PUBLIC_TITLE');
        $this->seed_customer();
        $id = self::FIXTURE_CUSTOMER_ID;
        $this->db()->query(
            "INSERT INTO action_recorder (module, user_id, user_name, identifier, success, date_added)
            VALUES ('ar_contact_us', {$id}, 'Fixture', '203.0.113.10', '1', NOW())"
        );
    }

    protected function tearDown(): void {
        $this->delete_customer();

        parent::tearDown();
    }

    public function test_execute_lists_recorded_ip_address(): void {
        $this->execute_module(cm_gdpr_ip_addresses::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-ip-addresses', $content);
        $this->assertStringContainsString('203.0.113.10', $content);
    }

}
