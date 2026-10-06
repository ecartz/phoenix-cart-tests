<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_account_stripe_sca_cards;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_account_stripe_sca_cards_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        if (is_file(DIR_FS_CATALOG . 'includes/languages/english/modules/content/account/cm_account_stripe_sca_cards.php')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/content/account/cm_account_stripe_sca_cards.php';
        }

        $this->define_constants([
            'MODULE_CONTENT_ACCOUNT_STRIPE_SCA_CARDS_STATUS' => 'True',
            'MODULE_CONTENT_ACCOUNT_STRIPE_SCA_CARDS_CONTENT_WIDTH' => 'col-sm-12',
        ]);

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_ACCOUNT_STRIPE_SCA_CARDS_TITLE' => 'Saved cards',
            'MODULE_PAYMENT_INSTALLED' => 'stripe_sca.php',
            'MODULE_PAYMENT_STRIPE_SCA_TRANSACTION_SERVER' => 'Live',
        ]);
        if (!class_exists('stripe_sca', false)) {
            eval('class stripe_sca { public bool $enabled = true; public string $code = "stripe_sca"; }');
        }
    }

    public function test_execute_populates_template_data(): void {
        $this->execute_module(cm_account_stripe_sca_cards::class);

        $link = $GLOBALS['Template']->_data['account']['account']['links']['stripe_sca_cards'] ?? null;
        $this->assertIsArray($link);
        $this->assertStringContainsString('stripe_sca/cards.php', (string) $link['link']);
    }

}
