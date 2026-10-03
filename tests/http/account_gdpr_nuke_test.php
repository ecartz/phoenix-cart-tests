<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_gdpr_nuke_test extends http_test_case {

    private string $throwaway_email = '';

    protected function tearDown(): void {
        if ($this->throwaway_email !== '') {
            http_customer_fixture_sql::delete_throwaway_customer_by_email($this->throwaway_email);
        }

        parent::tearDown();
    }

    public function test_throwaway_customer_can_nuke_account(): void {
        $this->throwaway_email = 'phoenix-http-nuke-' . uniqid('', true) . '@example.com';
        $password = 'phoenix-test';

        $create_page = $this->get_http()->request('GET', '/create_account.php');
        $formid = self::parse_hidden_input($create_page->getContent(false), 'formid');
        $this->get_http()->request('POST', '/create_account.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => 'Nuke',
                'lastname' => 'Me',
                'email_address' => $this->throwaway_email,
                'password' => $password,
                'street_address' => '1 Test Street',
                'city' => 'Testville',
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
                'telephone' => '555-0198',
                'newsletter' => '0',
                'matc' => '1',
            ],
        ]);

        $nuke_page = $this->get_http()->request('GET', '/ext/modules/content/account/nuke_account.php');
        $this->assertSame(200, $nuke_page->getStatusCode());
        $nuke_html = $nuke_page->getContent(false);
        $nuke_formid = self::parse_hidden_input($nuke_html, 'formid');
        $this->assertNotSame('', $nuke_formid);

        $response = $this->get_http()->request('POST', '/ext/modules/content/account/nuke_account.php', [
            'body' => [
                'nuke' => '1',
                'action' => 'process',
                'formid' => $nuke_formid,
                'password' => $password,
            ],
        ]);

        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringContainsString('index.php', $final_url);
        $this->assertNull(http_customer_fixture_sql::customer_id_for_email($this->throwaway_email));
        $this->throwaway_email = '';
    }

}
