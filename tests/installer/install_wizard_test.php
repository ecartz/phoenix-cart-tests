<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PHPUnit\Framework\Attributes\Group;

#[Group('installer')]
final class install_wizard_test extends install_test_case
{
    private const ADMIN_USERNAME = 'phoenix-install-admin';

    private const ADMIN_PASSWORD = 'phoenix-install-test';

    public function test_web_installer_configures_shop_and_admin_login(): void
    {
        $base_url = installer_bootstrap::base_url() . '/';
        $document_root = installer_bootstrap::catalog_filesystem_root();

        $welcome = $this->get_http()->request('GET', '/install/index.php');
        $this->assertSame(200, $welcome->getStatusCode());
        $welcome_body = $welcome->getContent(false);
        $this->assertStringContainsString('Welcome to Phoenix Cart', $welcome_body);
        $this->assertStringNotContainsString('table-danger', $welcome_body);

        $db_check = $this->get_http()->request('GET', '/install/rpc.php', [
            'query' => [
                'action' => 'dbCheck',
                'server' => installer_bootstrap::db_host(),
                'username' => installer_bootstrap::db_user(),
                'password' => installer_bootstrap::db_password(),
            ],
        ]);
        $this->assertSame(200, $db_check->getStatusCode());
        $this->assertStringStartsWith('1|', $db_check->getContent(false));

        $db_import = $this->get_http()->request('GET', '/install/rpc.php', [
            'query' => [
                'action' => 'dbImport',
                'server' => installer_bootstrap::db_host(),
                'username' => installer_bootstrap::db_user(),
                'password' => installer_bootstrap::db_password(),
                'name' => installer_bootstrap::installer_db_name(),
                'importsample' => '1',
            ],
        ]);
        $this->assertSame(200, $db_import->getStatusCode());
        $this->assertSame('1|Success', trim($db_import->getContent(false)));

        $db_fields = [
            'DB_SERVER' => installer_bootstrap::db_host(),
            'DB_SERVER_USERNAME' => installer_bootstrap::db_user(),
            'DB_SERVER_PASSWORD' => installer_bootstrap::db_password(),
            'DB_DATABASE' => installer_bootstrap::installer_db_name(),
            'DB_IMPORT_SAMPLE' => '1',
        ];

        $step2 = $this->get_http()->request('POST', '/install/install.php', [
            'query' => ['step' => '2'],
            'body' => $db_fields,
        ]);
        $this->assertSame(200, $step2->getStatusCode());
        $step2_body = $step2->getContent(false);
        $this->assertStringContainsString('DIR_FS_DOCUMENT_ROOT', $step2_body);

        $step3 = $this->get_http()->request('POST', '/install/install.php', [
            'query' => ['step' => '3'],
            'body' => $db_fields + [
                'HTTP_WWW_ADDRESS' => $base_url,
                'DIR_FS_DOCUMENT_ROOT' => $document_root,
            ],
        ]);
        $this->assertSame(200, $step3->getStatusCode());
        $step3_body = $step3->getContent(false);
        $this->assertStringContainsString('CFG_STORE_NAME', $step3_body);

        $step4 = $this->get_http()->request('POST', '/install/install.php', [
            'query' => ['step' => '4'],
            'body' => $db_fields + [
                'HTTP_WWW_ADDRESS' => $base_url,
                'DIR_FS_DOCUMENT_ROOT' => $document_root,
                'CFG_STORE_NAME' => 'Phoenix Installer Test Shop',
                'CFG_STORE_OWNER_NAME' => 'Fixture Owner',
                'CFG_STORE_OWNER_EMAIL_ADDRESS' => 'owner@example.com',
                'CFG_ADMINISTRATOR_USERNAME' => self::ADMIN_USERNAME,
                'CFG_ADMINISTRATOR_PASSWORD' => self::ADMIN_PASSWORD,
                'CFG_TIME_ZONE' => 'UTC',
            ],
        ]);
        $this->assertSame(200, $step4->getStatusCode());
        $this->assertStringContainsString('Finished!', $step4->getContent(false));

        $storefront = $this->get_http()->request('GET', '/');
        $this->assertSame(200, $storefront->getStatusCode());
        $store_url = (string) ($storefront->getInfo('url') ?? '');
        $this->assertStringNotContainsString('install/index.php', $store_url);
        $store_body = $storefront->getContent(false);
        $this->assertStringContainsString('Fruit', $store_body);

        $category = $this->get_http()->request('GET', '/index.php', [
            'query' => ['cPath' => '1'],
        ]);
        $this->assertSame(200, $category->getStatusCode());
        $this->assertStringContainsString('Citrus Fruit', $category->getContent(false));

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
                'username' => self::ADMIN_USERNAME,
                'password' => self::ADMIN_PASSWORD,
            ],
        ]);
        $this->assertContains($admin_login->getStatusCode(), [200, 302]);

        $admin_home = $admin_http->request('GET', '/admin/index.php');
        $this->assertSame(200, $admin_home->getStatusCode());
        $admin_home_url = (string) ($admin_home->getInfo('url') ?? '');
        $this->assertStringNotContainsString('login.php', $admin_home_url);
        $this->assertStringContainsString('display-4', $admin_home->getContent(false));
    }
}
