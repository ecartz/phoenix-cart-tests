<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_gdpr_cookies;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_gdpr_cookies_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language('modules/content/gdpr/cm_gdpr_cookies.php', 'MODULE_CONTENT_GDPR_COOKIES_PUBLIC_TITLE');
        $_COOKIE['phoenix_content'] = 'sample-cookie';
        $GLOBALS['port_my_data'] = [];
    }

    protected function tearDown(): void {
        unset($_COOKIE['phoenix_content'], $GLOBALS['port_my_data']);

        parent::tearDown();
    }

    public function test_execute_lists_request_cookie(): void {
        $this->execute_module(cm_gdpr_cookies::class);

        $content = $this->buffered_content('gdpr');
        $this->assertStringContainsString('cm-gdpr-cookies', $content);
        $this->assertStringContainsString('phoenix_content', $content);
        $this->assertStringContainsString('sample-cookie', $content);
    }

}
