<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_site_actions;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_site_actions_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/gdpr/cm_gdpr_site_actions.php',
            'MODULE_CONTENT_GDPR_SITE_ACTIONS_PUBLIC_TITLE'
        );
        $this->define_constants([
            'GDPR_FIXTURE_ACTION_LABEL' => 'Signed in',
        ]);
        $this->seed_customer();
        $id = self::FIXTURE_CUSTOMER_ID;
        $this->db()->query(
            "INSERT INTO action_recorder (module, user_id, user_name, identifier, success, date_added)
            VALUES ('GDPR_FIXTURE_ACTION_LABEL', {$id}, 'Fixture', 'content-test', '1', NOW())"
        );
    }

    protected function tearDown(): void {
        $this->delete_customer();

        parent::tearDown();
    }

    public function test_execute_lists_recorded_site_action(): void {
        $this->execute_module(cm_gdpr_site_actions::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-site-actions', $content);
        $this->assertStringContainsString('Signed in', $content);
    }

}
