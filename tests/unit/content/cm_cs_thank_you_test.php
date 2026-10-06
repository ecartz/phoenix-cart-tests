<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cs_thank_you;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cs_thank_you_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_CHECKOUT_SUCCESS_THANK_YOU_STATUS' => 'True',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_THANK_YOU_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_TEXT_THANKS_FOR_SHOPPING' => 'Thanks for shopping',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_TEXT_SUCCESS' => 'Your order is on its way',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_TEXT_SEE_ORDERS' => 'See your <a href="%s">orders</a>',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_TEXT_CONTACT_STORE_OWNER' => 'or <a href="%s">contact us</a>',
        ]);
    }

    public function test_execute_buffers_thank_you_links_into_checkout_success_group(): void {
        $this->execute_module(cm_cs_thank_you::class);

        $content = $this->buffered_content('checkout_success');
        $this->assertStringContainsString('cm-cs-thank-you', $content);
        $this->assertStringContainsString('Thanks for shopping', $content);
        $this->assertStringContainsString('account_history.php', $content);
        $this->assertStringContainsString('contact_us.php', $content);
    }

}
