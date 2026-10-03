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
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_version_check_page_renders(): void {
        $admin_http = $this->login_installed_admin();
        $response = $admin_http->request('GET', '/admin/version_check.php');
        $this->assertSame(200, $response->getStatusCode());
        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringNotContainsString('login.php', $final_url);
        $body = $response->getContent(false);
        $this->assertStringContainsString('Version Checker', $body);
        $this->assert_version_check_outcome($body);
    }

    public function test_admin_backup_now_lists_uncompressed_sql(): void {
        $admin_http = $this->login_installed_admin();

        $backup_form_page = $admin_http->request('GET', '/admin/backup.php', [
            'query' => ['action' => 'backup'],
        ]);
        $this->assertSame(200, $backup_form_page->getStatusCode());
        $form_html = $backup_form_page->getContent(false);
        $formid = self::parse_hidden_input($form_html, 'formid');
        $this->assertNotSame('', $formid);

        $backup_post = $admin_http->request('POST', '/admin/backup.php', [
            'query' => ['action' => 'backup_now'],
            'body' => [
                'formid' => $formid,
                'compress' => 'no',
            ],
        ]);
        $this->assertContains($backup_post->getStatusCode(), [200, 302]);

        $list_response = $admin_http->request('GET', '/admin/backup.php');
        $this->assertSame(200, $list_response->getStatusCode());
        $list_html = $list_response->getContent(false);
        $this->assertStringContainsString('db_', $list_html);
        $this->assertStringContainsString('.sql', $list_html);
    }

    public function test_admin_mail_compose_page_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/mail.php', [], 'Send Email To Customers');
    }

    public function test_admin_backup_manager_page_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/backup.php',
            [],
            'Database Backup Manager',
        );
    }

    public function test_admin_command_runner_help_lists_commands(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/command_runner.php',
            ['cmd' => 'help'],
            'Available Commands',
        );
    }

    private function assert_version_check_outcome(string $body): void {
        $outcomes = [
            'You are running the latest version of Phoenix.',
            'is the latest version available.',
            'Failed to load the available versions from the server.',
        ];

        foreach ($outcomes as $needle) {
            if (str_contains($body, $needle)) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail(
            'Expected version check page to contain one of: '
            . implode('; ', $outcomes),
        );
    }
}
