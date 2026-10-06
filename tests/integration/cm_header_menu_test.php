<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_header_menu;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_header_menu_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_category_tree();
        $this->load_language(
            'modules/content/header/cm_header_menu.php',
            'MODULE_CONTENT_HEADER_MENU_TITLE'
        );
        $this->define_constants([
            'BOOTSTRAP_THEME' => 'light',
            'IMAGE_BUTTON_CLOSE' => 'Close',
        ]);
    }

    protected function tearDown(): void {
        unset($GLOBALS['category_tree']);

        parent::tearDown();
    }

    public function test_execute_lists_sample_categories_and_brand(): void {
        $this->execute_module(cm_header_menu::class);

        $content = $this->buffered_content('header');
        $this->assertStringContainsString('cm-header-menu', $content);
        $this->assertStringContainsString('Fruit', $content);
        $this->assertStringContainsString('Fiacre', $content);
    }

}
