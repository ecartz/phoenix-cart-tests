<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_in_card_products;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_in_card_products_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/index_nested/cm_in_card_products.php',
            'MODULE_CONTENT_IN_CARD_PRODUCTS_HEADING'
        );
        $GLOBALS['current_category_id'] = 1;
    }

    protected function tearDown(): void {
        unset($GLOBALS['current_category_id']);

        parent::tearDown();
    }

    public function test_execute_shows_products_under_fruit_category(): void {
        $this->execute_module(cm_in_card_products::class);

        $content = $this->buffered_content('index_nested');
        $this->assertStringContainsString('cm-in-card-products', $content);
        $this->assertStringContainsString('Oranges', $content);
    }

}
