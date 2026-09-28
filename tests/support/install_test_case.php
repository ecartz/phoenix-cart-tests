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

        if (!is_dir(installer_bootstrap::catalog_copy_root() . DIRECTORY_SEPARATOR . 'install')) {
            throw new \RuntimeException(
                'Installer catalog missing at ' . installer_bootstrap::catalog_copy_root() . '. Run scripts/prepare-installer-catalog.sh first.'
            );
        }

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

    public static function tearDownAfterClass(): void
    {
        installer_bootstrap::cleanup_catalog();
        parent::tearDownAfterClass();
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

    protected static function parse_hidden_input(string $html, string $name): string
    {
        if (preg_match('/name="' . preg_quote($name, '/') . '"\s+value="([^"]*)"/', $html, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/value="([^"]*)"\s+name="' . preg_quote($name, '/') . '"/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }
}
