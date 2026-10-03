<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_preferences_test extends http_test_case {

    private const NEW_PASSWORD = 'phoenix-prefs-test';

    protected function tearDown(): void {
        http_customer_fixture_sql::restore_fixture_password_and_clear_reset_key();
        http_customer_fixture_sql::restore_fixture_newsletter();
        http_customer_fixture_sql::restore_fixture_global_product_notifications();
        parent::tearDown();
    }

    public function test_fixture_customer_can_update_password_newsletter_and_notifications(): void {
        $this->login_fixture_customer();

        $password_page = $this->get_http()->request('GET', '/account_password.php');
        $this->assertSame(200, $password_page->getStatusCode());
        $password_html = $password_page->getContent(false);
        $password_formid = self::parse_hidden_input($password_html, 'formid');
        $this->assertNotSame('', $password_formid);

        $this->get_http()->request('POST', '/account_password.php', [
            'body' => [
                'action' => 'process',
                'formid' => $password_formid,
                'password_current' => self::FIXTURE_CUSTOMER_PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ],
        ]);

        $this->get_http()->request('GET', '/logoff.php');
        $this->login_with_password(self::NEW_PASSWORD);

        $newsletter_page = $this->get_http()->request('GET', '/account_newsletters.php');
        $this->assertSame(200, $newsletter_page->getStatusCode());
        $newsletter_html = $newsletter_page->getContent(false);
        $newsletter_formid = self::parse_hidden_input($newsletter_html, 'formid');
        $this->assertNotSame('', $newsletter_formid);

        $newsletter_response = $this->get_http()->request('POST', '/account_newsletters.php', [
            'body' => [
                'action' => 'process',
                'formid' => $newsletter_formid,
                'newsletter_general' => '1',
            ],
        ]);
        $newsletter_url = (string) ($newsletter_response->getInfo('url') ?? '');
        $this->assertStringContainsString('account.php', $newsletter_url);
        $this->assertStringContainsString(
            'Your newsletter subscriptions have been successfully updated.',
            $newsletter_response->getContent(false)
        );

        $notifications_page = $this->get_http()->request('GET', '/account_notifications.php');
        $this->assertSame(200, $notifications_page->getStatusCode());
        $notifications_html = $notifications_page->getContent(false);
        $notifications_formid = self::parse_hidden_input($notifications_html, 'formid');
        $this->assertNotSame('', $notifications_formid);

        $notifications_response = $this->get_http()->request('POST', '/account_notifications.php', [
            'body' => [
                'action' => 'process',
                'formid' => $notifications_formid,
                'product_global' => '1',
            ],
        ]);
        $notifications_url = (string) ($notifications_response->getInfo('url') ?? '');
        $this->assertStringContainsString('account.php', $notifications_url);
        $this->assertStringContainsString(
            'Your product notifications have been successfully updated.',
            $notifications_response->getContent(false)
        );
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
