<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_asr_search_result;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;
use splitPageResults;

#[Group('mysql')]
final class cm_asr_search_result_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
        global $listing_sql;
        $listing_sql = 'SELECT p.products_id FROM products p WHERE p.products_status = 1';
        $GLOBALS['listing_split'] = new splitPageResults($listing_sql, 20, 'p.products_id');
    }

    protected function tearDown(): void {
        unset($GLOBALS['listing_sql'], $GLOBALS['listing_split']);

        parent::tearDown();
    }

    public function test_execute_buffers_search_results(): void {
        $this->execute_module(cm_asr_search_result::class);

        $content = $this->buffered_content('advanced_search_result');
        $this->assertStringContainsString('cm-asr-search-result', $content);
    }

}
