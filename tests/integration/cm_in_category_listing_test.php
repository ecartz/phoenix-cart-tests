<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_in_category_listing;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_in_category_listing_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $GLOBALS['current_category_id'] = 1;
        $GLOBALS['category_tree'] = new category_tree();
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_IN_CATEGORY_LISTING_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_IN_CATEGORY_LISTING_STATUS);

        $this->execute_module(cm_in_category_listing::class);

        $content = $this->buffered_content('index_nested');
        $this->assertStringContainsString('cm-in-category-listing', $content);
    }

}
