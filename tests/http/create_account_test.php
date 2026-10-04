<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_mail_capture;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class create_account_test extends http_test_case {

    private string $registered_email = '';

    protected function tearDown(): void {
        if ($this->registered_email !== '') {
            http_customer_fixture_sql::delete_throwaway_customer_by_email($this->registered_email);
        }

        parent::tearDown();
    }

    public function test_visitor_can_register_and_sign_in(): void {
        $this->registered_email = 'phoenix-http-register-' . uniqid('', true) . '@example.com';
        $password = 'phoenix-test';

        $create_page = $this->get_http()->request('GET', '/create_account.php');
        $this->assertSame(200, $create_page->getStatusCode());
        $create_html = $create_page->getContent(false);
        $formid = self::parse_hidden_input($create_html, 'formid');
        $this->assertNotSame('', $formid);
        $this->assertStringContainsString('firstname', $create_html);

        if (http_mail_capture::is_enabled()) {
            http_mail_capture::clear();
        }

        $registered = $this->get_http()->request('POST', '/create_account.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => 'Register',
                'lastname' => 'Visitor',
                'email_address' => $this->registered_email,
                'password' => $password,
                'street_address' => '1 Test Street',
                'city' => 'Testville',
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
                'telephone' => '555-0101',
                'newsletter' => '0',
                'matc' => '1',
            ],
        ]);
        $this->assertContains($registered->getStatusCode(), [200, 302]);

        $account_url = (string) ($registered->getInfo('url') ?? '');
        $this->assertTrue(
            str_contains($account_url, 'create_account_success.php') || str_contains($account_url, 'account.php'),
            'registration should land on success or account page'
        );

        if (http_mail_capture::is_enabled()) {
            $mail = http_mail_capture::read_combined();
            $this->assertStringContainsString($this->registered_email, $mail);
            $this->assertStringContainsString('Welcome to Phoenix', $mail);
            $this->assertStringContainsString('Your account is now active.', $mail);
        }

        $this->get_http()->request('GET', '/logoff.php');

        $login_page = $this->get_http()->request('GET', '/login.php');
        $login_formid = self::parse_hidden_input($login_page->getContent(false), 'formid');
        $this->get_http()->request('POST', '/login.php', [
            'body' => [
                'action' => 'process',
                'formid' => $login_formid,
                'email_address' => $this->registered_email,
                'password' => $password,
            ],
        ]);

        $account = $this->get_http()->request('GET', '/account.php');
        $this->assertStringContainsString('cm-account-title', $account->getContent(false));
    }

}
