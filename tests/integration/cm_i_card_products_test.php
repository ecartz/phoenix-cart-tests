<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_i_card_products;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_i_card_products_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_GET['cPath'] = '1';
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_CARD_PRODUCTS_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_CARD_PRODUCTS_STATUS);

        $this->execute_module(cm_i_card_products::class);

        $content = $this->buffered_content('index');
        $this->assertStringContainsString('cm-i-card-products', $content);
    }

}
