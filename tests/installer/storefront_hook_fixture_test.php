<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_storefront_hook_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class storefront_hook_fixture_test extends install_test_case {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    protected function tearDown(): void {
        installer_storefront_hook_bootstrap::remove_fixture_hook();
        parent::tearDown();
    }

    public function test_storefront_hook_marker_toggles_via_configuration(): void {
        installer_storefront_hook_bootstrap::install_fixture_hook();

        $shop_http = installer_bootstrap::client();
        $this->assert_homepage_marker($shop_http, true);

        installer_storefront_hook_bootstrap::set_marker_enabled(false);
        $this->assert_homepage_marker($shop_http, false);

        installer_storefront_hook_bootstrap::set_marker_enabled(true);
        $this->assert_homepage_marker($shop_http, true);

        $admin_http = $this->login_installed_admin();
        $hooks_html = $admin_http->request('GET', '/admin/modules_hooks.php')->getContent(false);
        $this->assertStringContainsString('http_storefront_hook_marker.php', $hooks_html);
        $this->assertStringContainsString('injectBodyEnd', $hooks_html);
    }

    private function assert_homepage_marker(HttpClientInterface $shop_http, bool $expected_present): void {
        $response = $shop_http->request('GET', '/');
        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);

        if ($expected_present) {
            $this->assertStringContainsString(installer_storefront_hook_bootstrap::MARKER_HTML, $body);
        } else {
            $this->assertStringNotContainsString(installer_storefront_hook_bootstrap::MARKER_HTML, $body);
        }
    }

}
