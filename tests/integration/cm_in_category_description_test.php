<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_in_category_description;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_in_category_description_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
        $GLOBALS['current_category_id'] = 1;
    }

    protected function tearDown(): void {
        unset($GLOBALS['current_category_id']);

        parent::tearDown();
    }

    public function test_execute_buffers_category_description(): void {
        $this->execute_module(cm_in_category_description::class);

        $content = $this->buffered_content('index_nested');
        $this->assertStringContainsString('cm-in-category-description', $content);
    }

}
