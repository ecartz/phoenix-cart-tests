<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_asr_title;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_asr_title_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
        $GLOBALS['keywords'] = 'oranges';
        $GLOBALS['listing_sql'] = 'SELECT p.products_id FROM products p WHERE p.products_status = 1';
    }

    protected function tearDown(): void {
        unset($GLOBALS['keywords'], $GLOBALS['listing_sql']);

        parent::tearDown();
    }

    public function test_execute_buffers_advanced_search_title(): void {
        $this->execute_module(cm_asr_title::class);

        $content = $this->buffered_content('advanced_search_result');
        $this->assertStringContainsString('cm-asr-title', $content);
    }

}
