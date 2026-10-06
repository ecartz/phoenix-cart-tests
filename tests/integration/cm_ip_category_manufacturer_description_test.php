<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_ip_category_manufacturer_description;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_ip_category_manufacturer_description_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
        $GLOBALS['current_category_id'] = 1;
        $_GET['cPath'] = '1';
    }

    protected function tearDown(): void {
        unset($GLOBALS['current_category_id'], $_GET['cPath']);

        parent::tearDown();
    }

    public function test_execute_buffers_category_manufacturer_description(): void {
        $this->execute_module(cm_ip_category_manufacturer_description::class);

        $content = $this->buffered_content('index_products');
        $this->assertStringContainsString('cm-ip-category-manufacturer-description', $content);
    }

}
