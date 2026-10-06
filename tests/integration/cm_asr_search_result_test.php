<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_asr_search_result;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;
use product_searcher;

#[Group('mysql')]
final class cm_asr_search_result_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/advanced_search_result/cm_asr_search_result.php',
            'MODULE_CONTENT_ASR_SEARCH_RESULT_TITLE'
        );
        $GLOBALS['search_keywords'] = ['Pears'];
        $GLOBALS['pfrom'] = '';
        $GLOBALS['pto'] = '';
        $GLOBALS['listing_sql'] = (new product_searcher([], []))->find();
        unset($_GET['sort']);
    }

    protected function tearDown(): void {
        unset($GLOBALS['search_keywords'], $GLOBALS['listing_sql'], $GLOBALS['pfrom'], $GLOBALS['pto'], $_GET['sort']);

        parent::tearDown();
    }

    public function test_execute_lists_sample_pears_in_search_results(): void {
        $this->execute_module(cm_asr_search_result::class);

        $content = $this->buffered_content('advanced_search_result');
        $this->assertStringContainsString('asr-search-result', $content);
        $this->assertStringContainsString('Pears', $content);
    }

}
