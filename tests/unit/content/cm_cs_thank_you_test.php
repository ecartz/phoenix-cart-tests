<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cs_thank_you;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cs_thank_you_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        if (is_file(DIR_FS_CATALOG . 'includes/languages/english/modules/content/checkout_success/cm_cs_thank_you.php')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/content/checkout_success/cm_cs_thank_you.php';
        }

        $this->define_constants([
            'MODULE_CONTENT_CHECKOUT_SUCCESS_THANK_YOU_STATUS' => 'True',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_THANK_YOU_CONTENT_WIDTH' => 'col-sm-12',
        ]);
    }

    public function test_execute_buffers_module_markup(): void {
        $this->execute_module(cm_cs_thank_you::class);

        $content = $this->buffered_content('checkout_success');
        $this->assertStringContainsString('cm-cs-thank-you', $content);
    }

}
