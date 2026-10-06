<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_contact_addresses;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_contact_addresses_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/gdpr/cm_gdpr_contact_addresses.php',
            'MODULE_CONTENT_GDPR_CONTACT_ADDRESSES_PUBLIC_TITLE'
        );
        $this->seed_customer(true);
    }

    protected function tearDown(): void {
        $this->delete_customer();

        parent::tearDown();
    }

    public function test_execute_lists_the_extra_address(): void {
        $this->execute_module(cm_gdpr_contact_addresses::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-contact-addresses', $content);
        $this->assertStringContainsString('Othercity', $content);
    }

}
