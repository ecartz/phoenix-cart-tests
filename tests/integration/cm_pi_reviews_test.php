<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_pi_reviews;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_pi_reviews_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/product_info/cm_pi_reviews.php',
            'MODULE_CONTENT_PRODUCT_INFO_REVIEWS_TEXT_TITLE'
        );
        $_GET['products_id'] = '4';
    }

    protected function tearDown(): void {
        unset($_GET['products_id']);

        parent::tearDown();
    }

    public function test_execute_shows_sample_apple_review(): void {
        $this->execute_module(cm_pi_reviews::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-reviews', $content);
        $this->assertStringContainsString('Lovely box of crunchy apples', $content);
    }

}
