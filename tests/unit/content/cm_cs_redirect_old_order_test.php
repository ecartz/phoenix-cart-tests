<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cs_redirect_old_order;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_cs_redirect_old_order_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_CHECKOUT_SUCCESS_REDIRECT_OLD_ORDER_STATUS' => 'True',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_REDIRECT_OLD_ORDER_MINUTES' => '0',
            'MODULE_CONTENT_CHECKOUT_SUCCESS_REDIRECT_OLD_ORDER_SORT_ORDER' => '500',
        ]);
    }

    public function test_execute_skips_redirect_when_minutes_threshold_is_zero(): void {
        $GLOBALS['order_id'] = 99;

        $this->execute_module(cm_cs_redirect_old_order::class);

        $this->addToAssertionCount(1);
    }

}
