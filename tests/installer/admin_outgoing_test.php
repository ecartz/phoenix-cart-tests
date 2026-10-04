<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_action_recorder_fixture_sql;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_mail_capture;
use PhoenixCart\Tests\support\installer_outgoing_lookup;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_outgoing_test extends install_test_case {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_outgoing_queue_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/outgoing.php', [], 'Outgoing Queue');
    }

    public function test_storefront_contact_us_shopowner_notification(): void {
        installer_action_recorder_fixture_sql::clear_module('ar_contact_us');
        $this->clear_captured_mail();

        $visitor_email = 'phoenix-install-contact-' . bin2hex(random_bytes(4)) . '@example.com';
        $enquiry = 'Installer acceptance enquiry from storefront contact_us.';

        $shop_http = installer_bootstrap::client();
        $response_body = $this->submit_storefront_contact_enquiry(
            $shop_http,
            'Installer Contact Visitor',
            $visitor_email,
            $enquiry,
        );
        $this->assertStringContainsString('Your message has been sent to the Shopowner.', $response_body);

        $outgoing_body = installer_outgoing_lookup::combined_body();
        if (str_contains($outgoing_body, $visitor_email)) {
            $admin_http = $this->login_installed_admin();
            $queue = $admin_http->request('GET', '/admin/outgoing.php');
            $this->assertSame(200, $queue->getStatusCode());
            $this->assertStringContainsString($visitor_email, $queue->getContent(false));

            return;
        }

        $this->require_installer_mail_capture();
        $this->assert_captured_mail_contains($visitor_email);

        $captured = installer_mail_capture::read_combined();
        if ($captured !== '') {
            $this->assertStringContainsString($enquiry, $captured);
        }
    }

    public function test_admin_outgoing_email_templates_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/outgoing_tpl.php',
            [],
            'Outgoing E-mail Templates',
        );
    }

    public function test_admin_layout_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'layout'],
            'Layout',
        );
    }

    public function test_admin_update_currency_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'currencies', 'list' => 'new'],
            'c_ecb',
        );
    }

    private function submit_storefront_contact_enquiry(
        HttpClientInterface $shop_http,
        string $name,
        string $email,
        string $enquiry,
    ): string {
        $shop_http->request('GET', '/contact_us.php');
        $page = $shop_http->request('GET', '/contact_us.php');
        $this->assertSame(200, $page->getStatusCode());
        $formid = self::parse_hidden_input($page->getContent(false), 'formid');
        $this->assertNotSame('', $formid);

        $response = $shop_http->request('POST', '/contact_us.php', [
            'body' => [
                'action' => 'send',
                'formid' => $formid,
                'name' => $name,
                'email' => $email,
                'enquiry' => $enquiry,
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

}
