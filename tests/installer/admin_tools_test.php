<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_tools_test extends install_test_case {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_database_tables_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/database_tables.php', [], 'products');
    }

    public function test_admin_server_info_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/server_info.php', [], 'PHP Version');
    }

    public function test_admin_security_checks_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/security_checks.php', [], 'sc_default_currency');
    }

    public function test_admin_templates_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/templates.php', [], 'display-4');
    }

    public function test_admin_language_explorer_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/language_explorer.php', [], 'english.php');
    }

    public function test_admin_modules_hooks_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/modules_hooks.php', [], 'startApplication');
    }

    public function test_admin_sec_dir_permissions_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/sec_dir_permissions.php', [], 'images');
    }

    public function test_admin_navbar_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'navbar_modules'],
            'Navbar',
        );
    }

    public function test_admin_info_pages_toggles_status(): void {
        $admin_http = $this->login_installed_admin();
        $info_html = $this->fetch_info_pages_list($admin_http);
        $this->assertStringContainsString('Privacy & Cookie Policy', $info_html);
        $enable_href = $this->extract_set_flag_href($info_html, '1');
        $this->assertNotSame('', $enable_href);

        $enable = $admin_http->request('GET', $this->admin_path_from_href($enable_href), [
            'query' => $this->admin_query_from_href($enable_href),
        ]);
        $this->assertContains($enable->getStatusCode(), [200, 302]);

        $after_enable = $this->fetch_info_pages_list($admin_http);
        $this->assertStringContainsString('fa-check-circle text-success', $after_enable);
        $this->assertStringContainsString('flag=0', $after_enable);

        $disable_href = $this->extract_set_flag_href($after_enable, '0');
        $this->assertNotSame('', $disable_href);

        $restore = $admin_http->request('GET', $this->admin_path_from_href($disable_href), [
            'query' => $this->admin_query_from_href($disable_href),
        ]);
        $this->assertContains($restore->getStatusCode(), [200, 302]);

        $after_disable = $this->fetch_info_pages_list($admin_http);
        $this->assertStringContainsString('fa-times-circle text-danger', $after_disable);
    }

    private function fetch_info_pages_list(HttpClientInterface $admin_http): string {
        $response = $admin_http->request('GET', '/admin/info_pages.php');
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

    private function extract_set_flag_href(string $html, string $flag): string {
        $pattern = '/href="([^"]*action=set_flag[^"]*flag=' . preg_quote($flag, '/') . '[^"]*)"/';
        if (preg_match($pattern, $html, $matches) !== 1) {
            return '';
        }

        return html_entity_decode($matches[1], ENT_QUOTES);
    }

    /**
     * @return array<string, string>
     */
    private function admin_query_from_href(string $href): array {
        $query_string = parse_url($href, PHP_URL_QUERY);
        if (!is_string($query_string) || $query_string === '') {
            return [];
        }

        $query = [];
        parse_str($query_string, $query);

        return array_map(static fn ($value) => (string) $value, $query);
    }

    private function admin_path_from_href(string $href): string {
        $path = parse_url($href, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/admin/')) {
            return $path;
        }

        if (str_starts_with($href, 'info_pages.php')) {
            return '/admin/info_pages.php';
        }

        return '/admin/' . ltrim($href, '/');
    }

}
