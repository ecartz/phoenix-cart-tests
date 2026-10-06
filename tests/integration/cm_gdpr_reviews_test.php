<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_reviews;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_reviews_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language('modules/content/gdpr/cm_gdpr_reviews.php', 'MODULE_CONTENT_GDPR_REVIEWS_PUBLIC_TITLE');
        $_SESSION['customer_id'] = 0;
        $GLOBALS['port_my_data'] = [];
    }

    protected function tearDown(): void {
        unset($_SESSION['customer_id'], $GLOBALS['port_my_data']);

        parent::tearDown();
    }

    public function test_execute_lists_sample_apple_review(): void {
        $this->execute_module(cm_gdpr_reviews::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-reviews', $content);
        $this->assertStringContainsString('Shiny Red Apples', $content);
    }

}
