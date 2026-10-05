<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_action_recorder_fixture_sql;
use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_mail_capture;
use PhoenixCart\Tests\support\http_notification_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class password_reset_test extends http_test_case {

    private const RESET_PASSWORD = 'phoenix-reset-test';

    protected function setUp(): void {
        parent::setUp();
        http_action_recorder_fixture_sql::clear_module('ar_reset_password');
        http_notification_fixture_sql::enable_password_forgotten();
        if (http_mail_capture::is_enabled()) {
            http_mail_capture::clear();
        }
    }

    protected function tearDown(): void {
        http_customer_fixture_sql::restore_fixture_password_and_clear_reset_key();
        http_action_recorder_fixture_sql::clear_module('ar_reset_password');
        http_notification_fixture_sql::restore_password_forgotten();
        parent::tearDown();
    }

    public function test_bad_formid_keeps_forgotten_form_without_success_message(): void {
        $forgot_page = $this->get_http()->request('GET', '/password_forgotten.php');
        $this->assertSame(200, $forgot_page->getStatusCode());

        $response = $this->get_http()->request('POST', '/password_forgotten.php', [
            'body' => [
                'action' => 'process',
                'formid' => '00000000000000000000000000000000',
                'email_address' => self::FIXTURE_CUSTOMER_EMAIL,
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('cm-forgot-password', $body);
        $this->assertStringNotContainsString('Check your email for a password reset link', $body);
        $this->assertNull(http_customer_fixture_sql::password_reset_key_for_fixture_customer());
    }

    public function test_second_forgotten_password_request_blocked_within_recorder_window(): void {
        $this->get_http()->request('GET', '/');

        $forgot_page = $this->get_http()->request('GET', '/password_forgotten.php');
        $first_formid = self::parse_hidden_input($forgot_page->getContent(false), 'formid');
        $this->assertNotSame('', $first_formid);

        $this->get_http()->request('POST', '/password_forgotten.php', [
            'body' => [
                'action' => 'process',
                'formid' => $first_formid,
                'email_address' => self::FIXTURE_CUSTOMER_EMAIL,
            ],
        ]);

        $this->assertNotNull(http_customer_fixture_sql::password_reset_key_for_fixture_customer());

        $mail_after_first = '';
        if (http_mail_capture::is_enabled()) {
            $mail_after_first = http_mail_capture::read_combined();
            $this->assertStringContainsString('Password Reset', $mail_after_first);
        }

        $second_page = $this->get_http()->request('GET', '/password_forgotten.php');
        $second_formid = self::parse_hidden_input($second_page->getContent(false), 'formid');
        $this->assertNotSame('', $second_formid);

        $second_response = $this->get_http()->request('POST', '/password_forgotten.php', [
            'body' => [
                'action' => 'process',
                'formid' => $second_formid,
                'email_address' => self::FIXTURE_CUSTOMER_EMAIL,
            ],
        ]);

        $second_body = $second_response->getContent(false);
        $this->assertStringContainsString('A password reset link has already been sent', $second_body);
        $this->assertStringContainsString('5 minutes', $second_body);

        if (http_mail_capture::is_enabled()) {
            $this->assertSame($mail_after_first, http_mail_capture::read_combined());
        }
    }

    public function test_password_forgotten_flow_resets_fixture_password(): void {
        $this->get_http()->request('GET', '/');

        $forgot_page = $this->get_http()->request('GET', '/password_forgotten.php');
        $forgot_html = $forgot_page->getContent(false);
        $formid = self::parse_hidden_input($forgot_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->get_http()->request('POST', '/password_forgotten.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'email_address' => self::FIXTURE_CUSTOMER_EMAIL,
            ],
        ]);

        $reset_key = http_customer_fixture_sql::password_reset_key_for_fixture_customer();
        $this->assertNotNull($reset_key);
        $this->assertSame(40, strlen($reset_key));

        if (http_mail_capture::is_enabled()) {
            $mail = http_mail_capture::read_combined();
            $this->assertStringContainsString('Password Reset', $mail);
            $this->assertStringContainsString(self::FIXTURE_CUSTOMER_EMAIL, $mail);
            $this->assertStringContainsString($reset_key, $mail);
        }

        $reset_get = $this->get_http()->request('GET', '/password_reset.php', [
            'query' => [
                'account' => self::FIXTURE_CUSTOMER_EMAIL,
                'key' => $reset_key,
            ],
        ]);
        $this->assertSame(200, $reset_get->getStatusCode());
        $reset_html = $reset_get->getContent(false);
        $reset_formid = self::parse_hidden_input($reset_html, 'formid');
        $this->assertNotSame('', $reset_formid);

        $this->get_http()->request('POST', '/password_reset.php', [
            'query' => [
                'account' => self::FIXTURE_CUSTOMER_EMAIL,
                'key' => $reset_key,
            ],
            'body' => [
                'action' => 'process',
                'formid' => $reset_formid,
                'password' => self::RESET_PASSWORD,
                'password_confirmation' => self::RESET_PASSWORD,
            ],
        ]);

        $this->login_with_password(self::RESET_PASSWORD);
    }

    private function login_with_password(string $password): void {
        $login_page = $this->get_http()->request('GET', '/login.php');
        $formid = self::parse_hidden_input($login_page->getContent(false), 'formid');
        $this->assertNotSame('', $formid);

        $this->get_http()->request('POST', '/login.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'email_address' => self::FIXTURE_CUSTOMER_EMAIL,
                'password' => $password,
            ],
        ]);

        $account = $this->get_http()->request('GET', '/account.php');
        $this->assertStringContainsString('cm-account-title', $account->getContent(false));
    }

}
