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

        $_SESSION['customer_id'] = 1;
        $GLOBALS['customer_id'] = 1;
        $GLOBALS['notify'] = new objectInfo(['products_id' => 1]);
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_CHECKOUT_SUCCESS_PRODUCT_NOTIFICATIONS_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_CHECKOUT_SUCCESS_PRODUCT_NOTIFICATIONS_STATUS);

        $this->execute_module(cm_cs_product_notifications::class);

        $content = $this->buffered_content('checkout_success');
        $this->assertStringContainsString('cm-cs-product-notifications', $content);
    }

}
