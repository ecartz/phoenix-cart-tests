<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_pi_price;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;
use Product;

#[Group('mysql')]
final class cm_pi_price_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
        $GLOBALS['product'] = new Product(['id' => 1, 'status' => 1]);
        $GLOBALS['currencies'] = new currencies();
    }

    protected function tearDown(): void {
        unset($GLOBALS['product'], $GLOBALS['currencies']);

        parent::tearDown();
    }

    public function test_execute_buffers_product_price(): void {
        $this->execute_module(cm_pi_price::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-price', $content);
    }

}
