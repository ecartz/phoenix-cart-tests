<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_cs_downloads;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_cs_downloads_test extends mysql_content_module_test_case {

    public function test_execute_respects_download_enabled_flag(): void {
        $this->assertTrue(defined('MODULE_CONTENT_CHECKOUT_SUCCESS_DOWNLOADS_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_CHECKOUT_SUCCESS_DOWNLOADS_STATUS);

        $this->execute_module(cm_cs_downloads::class);

        $content = $this->buffered_content('checkout_success');
        if (defined('DOWNLOAD_ENABLED') && DOWNLOAD_ENABLED === 'true') {
            $this->assertStringContainsString('cm-cs-downloads', $content);
        } else {
            $this->assertSame('', $content);
        }
    }

}
