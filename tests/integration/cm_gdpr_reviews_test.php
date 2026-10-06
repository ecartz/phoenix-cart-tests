<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_reviews;
use PhoenixCart\Tests\support\mysql_gdpr_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
#[Group('gdpr')]
final class cm_gdpr_reviews_test extends mysql_gdpr_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_GDPR_REVIEWS_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_GDPR_REVIEWS_STATUS);

        $this->execute_module(cm_gdpr_reviews::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-reviews', $content);
    }

}
