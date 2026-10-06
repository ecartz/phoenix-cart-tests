<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

use port_my_data;

/**
 * Shared session and globals for GDPR content-module integration tests.
 */
abstract class mysql_gdpr_content_module_test_case extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['customer_id'] = 1;
        $_SESSION['languages_id'] = 1;
        $this->with_linker();

        $GLOBALS['port_my_data'] = new port_my_data();
    }

    protected function tearDown(): void {
        unset($GLOBALS['port_my_data'], $_SESSION['customer_id']);

        parent::tearDown();
    }

}
