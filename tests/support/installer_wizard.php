<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

use PHPUnit\Framework\Assert;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Shared web-installer HTTP flow for installer acceptance tests.
 */
final class installer_wizard
{
    public const ADMIN_USERNAME = 'phoenix-install-admin';

    public const ADMIN_PASSWORD = 'phoenix-install-test';

    public const SAMPLE_STORE_NAME = 'Phoenix Installer Test Shop';

    public static function install_sample_shop(HttpClientInterface $http): void {
        self::clear_installed_configure();

        $base_url = installer_bootstrap::base_url() . '/';
        $document_root = installer_bootstrap::catalog_filesystem_root();

        $db_check = $http->request('GET', '/install/rpc.php', [
            'query' => [
                'action' => 'dbCheck',
                'server' => installer_bootstrap::db_host(),
                'username' => installer_bootstrap::db_user(),
                'password' => installer_bootstrap::db_password(),
            ],
        ]);
        Assert::assertSame(200, $db_check->getStatusCode());
        Assert::assertStringStartsWith('1|', $db_check->getContent(false));

        $db_import = $http->request('GET', '/install/rpc.php', [
            'query' => [
                'action' => 'dbImport',
                'server' => installer_bootstrap::db_host(),
                'username' => installer_bootstrap::db_user(),
                'password' => installer_bootstrap::db_password(),
                'name' => installer_bootstrap::installer_db_name(),
                'importsample' => '1',
            ],
        ]);
        Assert::assertSame(200, $db_import->getStatusCode());
        Assert::assertSame('1|Success', trim($db_import->getContent(false)));

        $db_fields = [
            'DB_SERVER' => installer_bootstrap::db_host(),
            'DB_SERVER_USERNAME' => installer_bootstrap::db_user(),
            'DB_SERVER_PASSWORD' => installer_bootstrap::db_password(),
            'DB_DATABASE' => installer_bootstrap::installer_db_name(),
            'DB_IMPORT_SAMPLE' => '1',
        ];

        $step2 = $http->request('POST', '/install/install.php', [
            'query' => ['step' => '2'],
            'body' => $db_fields,
        ]);
        Assert::assertSame(200, $step2->getStatusCode());
        Assert::assertStringContainsString('DIR_FS_DOCUMENT_ROOT', $step2->getContent(false));

        $step3 = $http->request('POST', '/install/install.php', [
            'query' => ['step' => '3'],
            'body' => $db_fields + [
                'HTTP_WWW_ADDRESS' => $base_url,
                'DIR_FS_DOCUMENT_ROOT' => $document_root,
            ],
        ]);
        Assert::assertSame(200, $step3->getStatusCode());
        Assert::assertStringContainsString('CFG_STORE_NAME', $step3->getContent(false));

        $step4 = $http->request('POST', '/install/install.php', [
            'query' => ['step' => '4'],
            'body' => $db_fields + [
                'HTTP_WWW_ADDRESS' => $base_url,
                'DIR_FS_DOCUMENT_ROOT' => $document_root,
                'CFG_STORE_NAME' => self::SAMPLE_STORE_NAME,
                'CFG_STORE_OWNER_NAME' => 'Fixture Owner',
                'CFG_STORE_OWNER_EMAIL_ADDRESS' => 'owner@example.com',
                'CFG_ADMINISTRATOR_USERNAME' => self::ADMIN_USERNAME,
                'CFG_ADMINISTRATOR_PASSWORD' => self::ADMIN_PASSWORD,
                'CFG_TIME_ZONE' => 'UTC',
            ],
        ]);
        Assert::assertSame(200, $step4->getStatusCode());
        Assert::assertStringContainsString('Finished!', $step4->getContent(false));

        $storefront = $http->request('GET', '/');
        Assert::assertSame(200, $storefront->getStatusCode());
        $store_url = (string) ($storefront->getInfo('url') ?? '');
        Assert::assertStringNotContainsString('install/index.php', $store_url);
        Assert::assertStringContainsString('Fruit', $storefront->getContent(false));

        $category = $http->request('GET', '/index.php', [
            'query' => ['cPath' => '1'],
        ]);
        Assert::assertSame(200, $category->getStatusCode());
        Assert::assertStringContainsString('Citrus Fruit', $category->getContent(false));
    }

    private static function clear_installed_configure(): void {
        $root = installer_bootstrap::catalog_copy_root();
        foreach ([
            $root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php',
            $root . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php',
        ] as $configure_path) {
            if (is_file($configure_path)) {
                @chmod($configure_path, 0666);
                @unlink($configure_path);
            }
        }
    }
}
