<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_cs_product_notifications;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_cs_product_notifications_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/checkout_success/cm_cs_product_notifications.php',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_PRODUCT_NOTIFICATIONS_TEXT_NOTIFY_PRODUCTS'
        );
        $this->seed_customer();
        $this->insert_order('Pears');
    }

    protected function tearDown(): void {
        $this->delete_customer();

        parent::tearDown();
    }

    public function test_execute_offers_notification_for_unordered_sample_product(): void {
        $this->execute_module(cm_cs_product_notifications::class);

        $content = $this->buffered_content('checkout_success');
        $this->assertStringContainsString('cm-cs-product-notifications', $content);
        $this->assertStringContainsString('Pears', $content);
    }

}
