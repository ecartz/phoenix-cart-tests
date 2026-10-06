<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_account_gdpr;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_account_gdpr_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $_SESSION['customer_id'] = 42;
        $GLOBALS['customer'] = new class {
            public function get(string $key): string {
                return '223';
            }
        };
        $this->define_constants([
            'MODULE_CONTENT_ACCOUNT_GDPR_STATUS' => 'True',
            'MODULE_CONTENT_ACCOUNT_GDPR_COUNTRIES' => '',
            'MODULE_CONTENT_ACCOUNT_GDPR_LINK_TITLE' => 'Data Overview',
            'MODULE_CONTENT_ACCOUNT_GDPR_SUB_TITLE' => 'View All Data',
        ]);
    }

    protected function tearDown(): void {
        unset($_SESSION['customer_id'], $GLOBALS['customer']);

        parent::tearDown();
    }

    public function test_execute_adds_gdpr_link_to_account_page(): void {
        $module = new cm_account_gdpr();
        $this->assertTrue($module->isEnabled());

        $this->execute_module(cm_account_gdpr::class);
        $this->build_account_page();

        $content = $this->buffered_content('account');
        $this->assertStringContainsString('View All Data', $content);
        $this->assertStringContainsString('gdpr.php', $content);
        $this->assertStringContainsString('Data Overview', $content);
    }

}
