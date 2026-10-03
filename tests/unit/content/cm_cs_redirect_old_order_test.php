<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_cs_redirect_old_order;
use PhoenixCart\Tests\support\content_module_test_case;
use ReflectionClass;

final class cm_cs_redirect_old_order_test extends content_module_test_case {

    public function test_module_defaults_redirect_after_sixty_minutes(): void {
        $module = new cm_cs_redirect_old_order();
        $reflection = new ReflectionClass($module);
        $method = $reflection->getMethod('get_parameters');
        $method->setAccessible(true);
        /** @var array<string, array<string, string>> $parameters */
        $parameters = $method->invoke($module);

        $this->assertSame(
            '60',
            $parameters['MODULE_CONTENT_CHECKOUT_SUCCESS_REDIRECT_OLD_ORDER_MINUTES']['value']
        );
        $this->assertSame(
            'True',
            $parameters['MODULE_CONTENT_CHECKOUT_SUCCESS_REDIRECT_OLD_ORDER_STATUS']['value']
        );
    }

}
