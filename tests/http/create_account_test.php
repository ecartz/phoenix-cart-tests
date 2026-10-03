<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class create_account_test extends http_test_case {

    private ?string $registered_email = null;

    protected function tearDown(): void {
        if ($this->registered_email !== null) {
            http_customer_fixture_sql::delete_customer_by_email($this->registered_email);
        }

        parent::tearDown();
    }

    public function test_visitor_can_register_from_create_account_page(): void {
        $this->registered_email = 'phoenix-http-create-' . uniqid('', true) . '@example.com';
        $password = 'phoenix-test';

        $this->get_http()->request('GET', '/');

        $create_page = $this->get_http()->request('GET', '/create_account.php');
        $this->assertSame(200, $create_page->getStatusCode());
        $create_html = $create_page->getContent(false);
        $this->assertStringContainsString('name="firstname"', $create_html);

        $formid = self::parse_hidden_input($create_html, 'formid');
        $this->assertNotSame('', $formid);

        $registered = $this->get_http()->request('POST', '/create_account.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => 'Created',
                'lastname' => 'ViaHttp',
                'email_address' => $this->registered_email,
                'password' => $password,
                'street_address' => '1 Register Street',
                'city' => 'Testville',
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
                'telephone' => '555-0101',
                'matc' => '1',
            ],
        ]);
        $this->assertContains($registered->getStatusCode(), [200, 302]);

        $account = $this->get_http()->request('GET', '/account.php');
        $this->assertStringContainsString('cm-account-title', $account->getContent(false));
        $this->assertTrue(http_customer_fixture_sql::customer_exists_with_email($this->registered_email));
    }

}
