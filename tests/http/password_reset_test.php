<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class password_reset_test extends http_test_case
{
    private const RESET_PASSWORD = 'phoenix-reset-test';

    protected function tearDown(): void {
        http_customer_fixture_sql::restore_fixture_password_and_clear_reset_key();
        parent::tearDown();
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
