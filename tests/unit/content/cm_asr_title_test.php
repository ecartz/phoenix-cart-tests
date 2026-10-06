<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_asr_title;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_asr_title_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_ASR_TITLE_STATUS' => 'True',
            'MODULE_CONTENT_ASR_TITLE_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_ASR_TITLE_PUBLIC_TITLE' => 'Search results for %s',
        ]);
        $GLOBALS['keywords'] = 'Pears';
    }

    protected function tearDown(): void {
        unset($GLOBALS['keywords']);

        parent::tearDown();
    }

    public function test_execute_buffers_keyword_alert_into_advanced_search_result_group(): void {
        $this->execute_module(cm_asr_title::class);

        $content = $this->buffered_content('advanced_search_result');
        $this->assertStringContainsString('cm-asr-title', $content);
        $this->assertStringContainsString('Search results for Pears', $content);
    }

}
