<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_pages_test extends install_test_case
{
    private const RENAMED_STORE_NAME = 'Phoenix Installer Renamed Shop';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_configuration_list_shows_store_name(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/configuration.php',
            ['gID' => '1'],
            installer_wizard::SAMPLE_STORE_NAME,
        );
    }

    public function test_admin_languages_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/languages.php', [], 'English');
    }

    public function test_admin_countries_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/countries.php', ['search' => 'United States'], 'United States');
    }

    public function test_admin_administrators_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/administrators.php',
            [],
            installer_wizard::ADMIN_USERNAME,
        );
    }

    public function test_admin_payment_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'payment'],
            'Cash on Delivery',
        );
    }

    public function test_admin_store_name_configuration_round_trip(): void {
        $admin_http = $this->login_installed_admin();
        $configuration_id = $this->resolve_store_name_configuration_id($admin_http);
        $this->assertNotSame('', $configuration_id);

        $this->post_configuration_value(
            $admin_http,
            $configuration_id,
            self::RENAMED_STORE_NAME,
        );
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/configuration.php',
            ['gID' => '1'],
            self::RENAMED_STORE_NAME,
        );

        $this->post_configuration_value(
            $admin_http,
            $configuration_id,
            installer_wizard::SAMPLE_STORE_NAME,
        );
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/configuration.php',
            ['gID' => '1'],
            installer_wizard::SAMPLE_STORE_NAME,
        );
    }

    private function resolve_store_name_configuration_id(HttpClientInterface $admin_http): string {
        $list = $admin_http->request('GET', '/admin/configuration.php', [
            'query' => ['gID' => '1'],
        ]);
        $this->assertSame(200, $list->getStatusCode());
        $html = $list->getContent(false);
        $this->assertStringContainsString(installer_wizard::SAMPLE_STORE_NAME, $html);

        if (preg_match_all(
            '/href="([^"]*configuration\.php\?[^"]*cID=(\d+)[^"]*)"/',
            $html,
            $matches,
            PREG_SET_ORDER,
        ) === false) {
            return '';
        }

        foreach ($matches as $match) {
            $configuration_id = $match[2];
            $query = $this->admin_query_from_href($match[1]);
            $query['action'] = 'edit';
            $detail = $admin_http->request('GET', $this->admin_path_from_href($match[1]), [
                'query' => $query,
            ]);
            if ($detail->getStatusCode() !== 200) {
                continue;
            }

            $edit_html = $detail->getContent(false);
            if (str_contains($edit_html, 'configuration_value')
                && str_contains($edit_html, installer_wizard::SAMPLE_STORE_NAME)) {
                return $configuration_id;
            }
        }

        return '';
    }

    private function post_configuration_value(
        HttpClientInterface $admin_http,
        string $configuration_id,
        string $value,
    ): void {
        $edit = $admin_http->request('GET', '/admin/configuration.php', [
            'query' => [
                'gID' => '1',
                'cID' => $configuration_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $edit->getStatusCode());
        $edit_html = $edit->getContent(false);
        $formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $formid);

        $save = $admin_http->request('POST', '/admin/configuration.php', [
            'query' => [
                'gID' => '1',
                'cID' => $configuration_id,
                'action' => 'save',
            ],
            'body' => [
                'formid' => $formid,
                'configuration_value' => $value,
            ],
        ]);
        $this->assertContains($save->getStatusCode(), [200, 302]);
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

        return array_map(static fn ($item) => (string) $item, $query);
    }

    private function admin_path_from_href(string $href): string {
        $path = parse_url($href, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/admin/')) {
            return $path;
        }

        if (str_starts_with($href, 'configuration.php')) {
            return '/admin/configuration.php';
        }

        return '/admin/' . ltrim($href, '/');
    }
}
