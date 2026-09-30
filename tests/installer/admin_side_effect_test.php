<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;

#[Group('installer')]
final class admin_side_effect_test extends install_test_case
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_version_check_page_renders(): void
    {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/version_check.php', [], 'Version Checker');
    }

    public function test_admin_mail_compose_page_renders(): void
    {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/mail.php', [], 'Send Email To Customers');
    }

    public function test_admin_backup_manager_page_renders(): void
    {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/backup.php',
            [],
            'Database Backup Manager',
        );
    }

    public function test_admin_command_runner_help_lists_commands(): void
    {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/command_runner.php',
            ['cmd' => 'help'],
            'Available Commands',
        );
    }
}
