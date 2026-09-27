<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cas_continue_button;
use navigationHistory;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cas_continue_button_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_CAS_CONTINUE_BUTTON_STATUS' => 'True',
            'MODULE_CONTENT_CAS_CONTINUE_BUTTON_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_CAS_CONTINUE_BUTTON_TEXT' => 'Continue',
        ]);

        $navigation = new navigationHistory();
        $navigation->set_snapshot([
            'page' => 'products.php',
            'get' => ['cPath' => '1_2'],
        ]);
        $_SESSION['navigation'] = $navigation;
    }

    protected function tearDown(): void
    {
        unset($_SESSION['navigation']);

        parent::tearDown();
    }

    public function test_execute_buffers_continue_button_using_navigation_snapshot(): void
    {
        $this->execute_module(cm_cas_continue_button::class);

        $content = $this->buffered_content('create_account_success');
        $this->assertStringContainsString('Continue', $content);
        $this->assertStringContainsString('products.php', $content);
        $this->assertStringContainsString('cPath=1_2', $content);
        $this->assertStringContainsString('cm-cas-continue-button', $content);
        $this->assertSame([], $_SESSION['navigation']->snapshot);
    }

    public function test_execute_falls_back_to_index_when_snapshot_empty(): void
    {
        $_SESSION['navigation'] = new navigationHistory();
        $this->reset_template();

        $this->execute_module(cm_cas_continue_button::class);

        $content = $this->buffered_content('create_account_success');
        $this->assertStringContainsString('index.php', $content);
        $this->assertStringContainsString('Continue', $content);
    }
}
