<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_account_stripe_sca_cards;
use PhoenixCart\Tests\support\content_module_test_case;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class cm_account_stripe_sca_cards_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $GLOBALS['PHP_SELF'] = 'index.php';
        $this->define_constants([
            'MODULE_CONTENT_ACCOUNT_STRIPE_SCA_CARDS_STATUS' => 'True',
            'MODULE_CONTENT_ACCOUNT_STRIPE_SCA_CARDS_SORT_ORDER' => '0',
            'MODULE_PAYMENT_INSTALLED' => 'stripe_sca.php',
            'MODULE_PAYMENT_STRIPE_SCA_STATUS' => 'True',
            'MODULE_PAYMENT_STRIPE_SCA_TRANSACTION_SERVER' => 'Test',
            'MODULE_PAYMENT_STRIPE_SCA_TEST_PUBLISHABLE_KEY' => 'pk_test_phoenix',
            'MODULE_PAYMENT_STRIPE_SCA_TEST_SECRET_KEY' => 'sk_test_phoenix',
            'MODULE_PAYMENT_STRIPE_SCA_LIVE_PUBLISHABLE_KEY' => '',
            'MODULE_PAYMENT_STRIPE_SCA_LIVE_SECRET_KEY' => '',
            'MODULE_PAYMENT_STRIPE_SCA_ZONE' => '0',
        ]);

        if (!defined('MODULE_CONTENT_ACCOUNT_STRIPE_SCA_CARDS_TITLE')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/content/account/cm_account_stripe_sca_cards.php';
        }
        if (!defined('MODULE_PAYMENT_STRIPE_SCA_TEXT_TITLE')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/payment/stripe_sca.php';
        }

        $GLOBALS['Template']->_data['account']['account'] = [
            'title' => 'My Account',
            'sort_order' => 10,
            'links' => [],
        ];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_adds_saved_cards_link_when_stripe_module_enabled(): void {
        $module = new cm_account_stripe_sca_cards();
        $this->assertTrue($module->isEnabled());

        $module->execute();
        $this->build_account_page();

        $content = $this->buffered_content('account');
        $this->assertStringContainsString('stripe_sca/cards.php', $content);
        $this->assertStringContainsString('Stripe SCA Cards Management Page', $content);
        $this->assertStringContainsString('Test', $content);
    }

}
