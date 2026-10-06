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

        $this->prepare_category_tree();
        $this->load_language(
            'modules/content/index_nested/cm_in_category_listing.php',
            'MODULE_CONTENT_IN_CATEGORY_LISTING_TITLE'
        );
        $GLOBALS['current_category_id'] = 1;
    }

    protected function tearDown(): void {
        unset($GLOBALS['current_category_id'], $GLOBALS['category_tree']);

        parent::tearDown();
    }

    public function test_execute_lists_child_categories_under_fruit(): void {
        $this->execute_module(cm_in_category_listing::class);

        $content = $this->buffered_content('index_nested');
        $this->assertStringContainsString('cm-in-category-listing', $content);
        $this->assertStringContainsString('Apples & Pears', $content);
        $this->assertStringContainsString('Citrus Fruit', $content);
    }

}
