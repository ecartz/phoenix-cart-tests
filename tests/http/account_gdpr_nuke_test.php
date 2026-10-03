<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_gdpr_nuke_test extends http_test_case {

    private ?string $throwaway_email = null;

    protected function tearDown(): void {
        if ($this->throwaway_email !== null) {
            http_customer_fixture_sql::delete_customer_by_email($this->throwaway_email);
        }

        parent::tearDown();
    }

    public function test_throwaway_customer_can_nuke_account_via_gdpr_form(): void {
        $this->throwaway_email = 'phoenix-http-nuke-' . uniqid('', true) . '@example.com';
        $password = 'phoenix-test';

        $this->register_throwaway_customer($password);
        $this->assertTrue(http_customer_fixture_sql::customer_exists_with_email($this->throwaway_email));

        $nuke_page = $this->get_http()->request('GET', '/ext/modules/content/account/nuke_account.php');
        $this->assertSame(200, $nuke_page->getStatusCode());
        $nuke_html = $nuke_page->getContent(false);
        $formid = self::parse_hidden_input($nuke_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->get_http()->request('POST', '/ext/modules/content/account/nuke_account.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'nuke' => '1',
                'password' => $password,
            ],
        ]);

        $this->assertFalse(http_customer_fixture_sql::customer_exists_with_email($this->throwaway_email));

        $account = $this->get_http()->request('GET', '/account.php');
        $account_url = (string) ($account->getInfo('url') ?? '');
        $this->assertMatchesRegularExpression('#/(login|create_account)\.php#', $account_url);
    }

    private function register_throwaway_customer(string $password): void {
        $this->get_http()->request('GET', '/');

        $create_page = $this->get_http()->request('GET', '/create_account.php');
        $formid = self::parse_hidden_input($create_page->getContent(false), 'formid');
        $this->assertNotSame('', $formid);

        $this->get_http()->request('POST', '/create_account.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => 'Nuke',
                'lastname' => 'Me',
                'email_address' => $this->throwaway_email,
                'password' => $password,
                'street_address' => '1 Delete Street',
                'city' => 'Testville',
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
                'telephone' => '555-0198',
                'matc' => '1',
            ],
        ]);
    }

}
