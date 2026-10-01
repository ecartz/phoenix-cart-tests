<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_pages_test extends http_test_case
{
    protected function tearDown(): void
    {
        http_customer_fixture_sql::restore_fixture_firstname();
        parent::tearDown();
    }

    public function test_fixture_customer_account_pages_and_edit_profile(): void
    {
        $this->login_fixture_customer();

        foreach (
            [
                '/account_edit.php' => 'firstname',
                '/account_password.php' => 'password_current',
                '/account_history.php' => 'Account History',
                '/account_newsletters.php' => 'Newsletter',
                '/account_notifications.php' => 'Notifications',
            ] as $path => $needle
        ) {
            $response = $this->get_http()->request('GET', $path);
            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString($needle, $response->getContent(false));
        }

        $edit_page = $this->get_http()->request('GET', '/account_edit.php');
        $edit_html = $edit_page->getContent(false);
        $formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->get_http()->request('POST', '/account_edit.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => 'Edited',
                'lastname' => 'Customer',
                'email_address' => self::FIXTURE_CUSTOMER_EMAIL,
                'telephone' => '555-0100',
            ],
        ]);

        $account = $this->get_http()->request('GET', '/account.php');
        $this->assertStringContainsString('Edited', $account->getContent(false));

        $this->get_http()->request('GET', '/logoff.php');
        $after_logoff = $this->get_http()->request('GET', '/account.php');
        $after_url = (string) ($after_logoff->getInfo('url') ?? '');
        $this->assertStringContainsString('login.php', $after_url);
    }
}
