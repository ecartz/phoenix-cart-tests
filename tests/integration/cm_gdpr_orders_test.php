<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_orders;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_orders_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language('modules/content/gdpr/cm_gdpr_orders.php', 'MODULE_CONTENT_GDPR_ORDERS_PUBLIC_TITLE');
        $this->seed_customer();
        $this->insert_order('Pears');
    }

    protected function tearDown(): void {
        $this->delete_customer();

        parent::tearDown();
    }

    public function test_execute_lists_fixture_order_total(): void {
        $this->execute_module(cm_gdpr_orders::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-orders', $content);
        $this->assertStringContainsString('$12.00', $content);
    }

}
