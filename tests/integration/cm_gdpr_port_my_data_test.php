<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_port_my_data;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_port_my_data_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/gdpr/cm_gdpr_port_my_data.php',
            'MODULE_CONTENT_GDPR_PORT_MY_DATA_BUTTON_TEXT'
        );
        $GLOBALS['port_my_data'] = [];
    }

    protected function tearDown(): void {
        unset($GLOBALS['port_my_data']);

        parent::tearDown();
    }

    public function test_execute_buffers_export_button(): void {
        $this->execute_module(cm_gdpr_port_my_data::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-port-my-data', $content);
        $this->assertStringContainsString('gdpr.php', $content);
        $this->assertStringContainsString('gdpr_data', $content);
    }

}
