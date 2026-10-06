<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_account_gdpr;
use PhoenixCart\Tests\support\content_module_customer_stub;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_account_gdpr_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        if (is_file(DIR_FS_CATALOG . 'includes/languages/english/modules/content/account/cm_account_gdpr.php')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/content/account/cm_account_gdpr.php';
        }

        $this->define_constants([
            'MODULE_CONTENT_ACCOUNT_GDPR_STATUS' => 'True',
            'MODULE_CONTENT_ACCOUNT_GDPR_CONTENT_WIDTH' => 'col-sm-12',
        ]);

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_ACCOUNT_GDPR_COUNTRIES' => '',
            'MODULE_CONTENT_ACCOUNT_GDPR_LINK_TITLE' => 'Privacy',
            'MODULE_CONTENT_ACCOUNT_GDPR_SUB_TITLE' => 'GDPR Tools',
        ]);
        $_SESSION['customer_id'] = 1;
        $GLOBALS['customer'] = new content_module_customer_stub();
    }

    protected function tearDown(): void {
        unset($_SESSION['customer_id'], $_SESSION['cart'], $GLOBALS['product'], $GLOBALS['customer']);
        unset($_GET['products_id'], $_GET['cPath']);
        unset($GLOBALS['keywords'], $GLOBALS['listing_sql'], $GLOBALS['listing_split']);
        unset($GLOBALS['current_category_id'], $GLOBALS['category_tree']);

        parent::tearDown();
    }

    public function test_execute_populates_template_data(): void {
        $this->execute_module(cm_account_gdpr::class);

        $this->assertArrayHasKey('gdpr', $GLOBALS['Template']->_data['account']);
        $link = $GLOBALS['Template']->_data['account']['gdpr']['links']['account'] ?? null;
        $this->assertIsArray($link);
        $this->assertStringContainsString('gdpr.php', (string) $link['link']);
    }

}
