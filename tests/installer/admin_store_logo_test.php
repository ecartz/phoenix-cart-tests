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
        $original_logo = $this->fetch_store_logo_filename();
        $this->assertNotSame('', $original_logo);

        $catalog_root = installer_bootstrap::catalog_copy_root();
        $original_logo_path = $this->store_logo_image_path($catalog_root, $original_logo);
        $this->assertFileExists($original_logo_path);
        $original_logo_bytes = file_get_contents($original_logo_path);
        $this->assertIsString($original_logo_bytes);
        $original_logo_size = strlen($original_logo_bytes);

        $backup_path = $this->store_logo_backup_path($original_logo);
        if (!is_dir(dirname($backup_path)) && !mkdir(dirname($backup_path), 0775, true) && !is_dir(dirname($backup_path))) {
            $this->fail('Cannot create store logo backup directory: ' . dirname($backup_path));
        }
        file_put_contents($backup_path, $original_logo_bytes);

        $edit_html = $this->fetch_admin_page($admin_http, '/admin/store_logo.php', ['action' => 'edit']);
        $formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $formid);
        $this->post_admin_multipart(
            $admin_http,
            '/admin/store_logo.php',
            ['action' => 'save'],
            ['formid' => $formid],
            ['store_logo' => $fixture_path],
        );

        $uploaded_logo = $this->fetch_store_logo_filename();
        $this->assertStringContainsString('installer-store-logo-test', $uploaded_logo);
        $uploaded_logo_path = $this->store_logo_image_path($catalog_root, $uploaded_logo);
        $this->assertFileExists($uploaded_logo_path);
        $this->assertNotSame($original_logo_size, filesize($uploaded_logo_path));

        $store_logo_admin = $this->fetch_admin_page($admin_http, '/admin/store_logo.php');
        $this->assertStringContainsString('images/' . $uploaded_logo, $store_logo_admin);

        $restore_edit = $this->fetch_admin_page($admin_http, '/admin/store_logo.php', ['action' => 'edit']);
        $restore_formid = self::parse_hidden_input($restore_edit, 'formid');
        $this->assertNotSame('', $restore_formid);
        $this->post_admin_multipart(
            $admin_http,
            '/admin/store_logo.php',
            ['action' => 'save'],
            ['formid' => $restore_formid],
            ['store_logo' => $backup_path],
        );

        $this->assertSame($original_logo, $this->fetch_store_logo_filename());
        $restored_logo_path = $this->store_logo_image_path($catalog_root, $original_logo);
        $this->assertFileExists($restored_logo_path);
        $this->assertSame($original_logo_size, filesize($restored_logo_path));
        $restored_logo_bytes = file_get_contents($restored_logo_path);
        $this->assertIsString($restored_logo_bytes);
        $this->assertSame($original_logo_bytes, $restored_logo_bytes);

        @unlink($backup_path);
    }

    private function fetch_store_logo_filename(): string
    {
        $mysqli = new \mysqli(
            installer_bootstrap::db_host(),
            installer_bootstrap::db_user(),
            installer_bootstrap::db_password(),
            installer_bootstrap::installer_db_name(),
            (int) installer_bootstrap::db_port(),
        );

        if ($mysqli->connect_errno) {
            $this->fail('MySQL connect failed: ' . $mysqli->connect_error);
        }

        $mysqli->set_charset('utf8mb4');

        $statement = $mysqli->prepare(
            'SELECT configuration_value FROM configuration WHERE configuration_key = ? LIMIT 1',
        );
        if ($statement === false) {
            $mysqli->close();
            $this->fail('Prepare failed: ' . $mysqli->error);
        }

        $configuration_key = 'STORE_LOGO';
        $statement->bind_param('s', $configuration_key);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            return '';
        }

        return (string) $row['configuration_value'];
    }

    private function store_logo_image_path(string $catalog_root, string $filename): string
    {
        return $catalog_root . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $filename;
    }

    private function store_logo_backup_path(string $original_filename): string
    {
        $repo_root = dirname(__DIR__, 2);

        return $repo_root . DIRECTORY_SEPARATOR . 'working' . DIRECTORY_SEPARATOR . 'installer-store-logo-backup'
            . DIRECTORY_SEPARATOR . $original_filename;
    }
}
