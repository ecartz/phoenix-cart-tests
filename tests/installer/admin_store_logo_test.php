<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_admin_writes;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;

#[Group('installer')]
final class admin_store_logo_test extends install_test_case
{
    use installer_admin_writes;

    private const FIXTURE_LOGO = 'fixtures/installer-store-logo-test.png';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_store_logo_upload_and_restore(): void
    {
        $fixture_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::FIXTURE_LOGO);
        $this->assertFileExists($fixture_path);

        $admin_http = $this->login_installed_admin();
        $edit_html = $this->fetch_admin_page($admin_http, '/admin/store_logo.php', ['action' => 'edit']);
        $original_logo = $this->parse_store_logo_filename($edit_html);
        $this->assertNotSame('', $original_logo);

        $catalog_root = installer_bootstrap::catalog_copy_root();
        $original_logo_path = $catalog_root . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $original_logo;
        if (!is_file($original_logo_path)) {
            $this->markTestSkipped('Default store logo file is missing at ' . $original_logo_path);
        }
        $this->assertFileExists($original_logo_path);
        $original_logo_bytes = file_get_contents($original_logo_path);
        $this->assertIsString($original_logo_bytes);

        $backup_path = $catalog_root . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . '.installer-logo-backup.png';
        file_put_contents($backup_path, $original_logo_bytes);

        $formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $formid);
        $this->post_admin_multipart(
            $admin_http,
            '/admin/store_logo.php',
            ['action' => 'save'],
            ['formid' => $formid],
            ['store_logo' => $fixture_path],
        );

        $after_upload = $this->fetch_admin_page($admin_http, '/admin/store_logo.php');
        $uploaded_logo = $this->parse_store_logo_filename($after_upload);
        $this->assertStringContainsString('installer-store-logo-test', $uploaded_logo);

        $shop_http = installer_bootstrap::client();
        $home = $shop_http->request('GET', '/');
        $this->assertSame(200, $home->getStatusCode());
        $this->assertStringContainsString('images/' . $uploaded_logo, $home->getContent(false));

        $restore_temp = $catalog_root . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $original_logo;
        file_put_contents($restore_temp, $original_logo_bytes);

        $restore_edit = $this->fetch_admin_page($admin_http, '/admin/store_logo.php', ['action' => 'edit']);
        $restore_formid = self::parse_hidden_input($restore_edit, 'formid');
        $this->assertNotSame('', $restore_formid);
        $this->post_admin_multipart(
            $admin_http,
            '/admin/store_logo.php',
            ['action' => 'save'],
            ['formid' => $restore_formid],
            ['store_logo' => $restore_temp],
        );

        $after_restore = $this->fetch_admin_page($admin_http, '/admin/store_logo.php');
        $restored_logo = $this->parse_store_logo_filename($after_restore);
        $this->assertSame($original_logo, $restored_logo);

        @unlink($backup_path);
    }

    private function parse_store_logo_filename(string $html): string
    {
        if (preg_match('/images\/([^"\'?\s>]+\.(?:png|gif|jpe?g|svg|webp))/i', $html, $matches) === 1) {
            return html_entity_decode($matches[1], ENT_QUOTES);
        }

        return '';
    }
}
