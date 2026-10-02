<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class install_test_case extends phoenix_test_case
{
    private HttpClientInterface $http;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!installer_bootstrap::is_enabled()) {
            self::markTestSkipped(
                'Installer tests skipped. Set PHOENIX_INSTALLER_ENABLED=1 and run composer test:installer (or prepare catalog, reset DB, and scripts/installer-server.sh).'
            );
        }

        if (!is_dir(installer_bootstrap::catalog_copy_root())) {
            throw new \RuntimeException(
                'Installer catalog missing at ' . installer_bootstrap::catalog_copy_root() . '. Run scripts/prepare-installer-catalog.sh first.'
            );
        }

        installer_bootstrap::ensure_install_directory();

        installer_bootstrap::reset_installer_database();

        $probe = installer_bootstrap::client();

        try {
            $response = $probe->request('GET', '/install/index.php');
            $status = $response->getStatusCode();
            if ($status >= 500) {
                throw new \RuntimeException('Installer returned HTTP ' . $status);
            }
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                'Installer shop not reachable at ' . installer_bootstrap::base_url() . ': ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = installer_bootstrap::client();
    }

    protected function get_http(): HttpClientInterface
    {
        return $this->http;
    }

    protected function login_installed_admin(): HttpClientInterface
    {
        $admin_http = installer_bootstrap::client();

        $admin_login_page = $admin_http->request('GET', '/admin/login.php');
        $this->assertSame(200, $admin_login_page->getStatusCode());
        $admin_html = $admin_login_page->getContent(false);
        $formid = self::parse_hidden_input($admin_html, 'formid');
        $this->assertNotSame('', $formid);

        $admin_login = $admin_http->request('POST', '/admin/login.php', [
            'query' => ['action' => 'process'],
            'body' => [
                'formid' => $formid,
                'username' => installer_wizard::ADMIN_USERNAME,
                'password' => installer_wizard::ADMIN_PASSWORD,
            ],
        ]);
        $this->assertContains($admin_login->getStatusCode(), [200, 302]);

        return $admin_http;
    }

    protected function assert_admin_get_page(
        HttpClientInterface $admin_http,
        string $path,
        array $query,
        string $body_contains,
    ): void {
        $response = $admin_http->request('GET', $path, ['query' => $query]);
        $this->assertSame(200, $response->getStatusCode());
        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringNotContainsString('login.php', $final_url);
        $this->assertStringContainsString($body_contains, $response->getContent(false));
    }

    protected static function parse_hidden_input(string $html, string $name): string
    {
        $quoted = preg_quote($name, '/');

        if (preg_match('/name="' . $quoted . '"[^>]*\svalue="([^"]*)"/', $html, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/value="([^"]*)"[^>]*\sname="' . $quoted . '"/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    protected static function parse_formid_from_page(string $html): string
    {
        $formid = self::parse_hidden_input($html, 'formid');
        if ($formid !== '') {
            return $formid;
        }

        if (preg_match('/formid=([a-f0-9]+)/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    protected function require_installer_mail_capture(): void
    {
        if (!installer_mail_capture::is_enabled()) {
            $this->markTestSkipped(
                'Installer mail capture is disabled. Run composer test:installer (or scripts/installer-server.sh with sendmail_path) on Linux.'
            );
        }
    }

    protected function clear_captured_mail(): void
    {
        installer_mail_capture::clear();
    }

    protected function assert_captured_mail_contains(string $needle): void
    {
        $body = installer_mail_capture::read_combined();
        if ($body === '') {
            $body = installer_outgoing_lookup::combined_body();
        }

        $this->assertStringContainsString(
            $needle,
            $body,
            'Expected captured installer mail or outgoing queue to contain: ' . $needle,
        );
    }
}
