<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_ip_title;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_ip_title_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_category_tree();
        $this->load_language(
            'modules/content/index_products/cm_ip_title.php',
            'MODULE_CONTENT_IP_TITLE_PUBLIC_TITLE'
        );
        $GLOBALS['current_category_id'] = 3;
        unset($GLOBALS['brand']);
    }

    protected function tearDown(): void {
        unset($GLOBALS['current_category_id'], $GLOBALS['category_tree'], $GLOBALS['brand']);

        parent::tearDown();
    }

    public function test_execute_buffers_category_name_heading(): void {
        $this->execute_module(cm_ip_title::class);

        $content = $this->buffered_content('index_products');
        $this->assertStringContainsString('cm-ip-title', $content);
        $this->assertStringContainsString('Apples & Pears', $content);
    }

}
