<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_account_gdpr_nuke;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_account_gdpr_nuke_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $_SESSION['customer_id'] = 42;
        $GLOBALS['Template']->_data['account']['gdpr'] = [
            'title' => 'Privacy',
            'sort_order' => 100,
            'links' => [],
        ];
        $this->define_constants([
            'MODULE_CONTENT_ACCOUNT_GDPR_NUKE_STATUS' => 'True',
            'MODULE_CONTENT_ACCOUNT_GDPR_NUKE_COUNTRIES' => '',
            'MODULE_CONTENT_ACCOUNT_GDPR_NUKE_LINK_TITLE' => 'Delete My Account',
        ]);
    }

    protected function tearDown(): void {
        unset($_SESSION['customer_id']);

        parent::tearDown();
    }

    public function test_execute_adds_delete_account_link_when_countries_unrestricted(): void {
        $module = new cm_account_gdpr_nuke();
        $this->assertTrue($module->isEnabled());

        $this->execute_module(cm_account_gdpr_nuke::class);
        $this->build_account_page();

        $content = $this->buffered_content('account');
        $this->assertStringContainsString('Delete My Account', $content);
        $this->assertStringContainsString('nuke_account.php', $content);
    }

}
