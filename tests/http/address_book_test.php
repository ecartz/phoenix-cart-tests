<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class address_book_test extends http_test_case
{
    private ?int $extra_address_id = null;

    protected function tearDown(): void
    {
        if ($this->extra_address_id !== null) {
            http_customer_fixture_sql::delete_address_book_entry($this->extra_address_id);
        }

        parent::tearDown();
    }

    public function test_customer_can_add_and_delete_secondary_address(): void
    {
        $this->login_fixture_customer();

        $new_page = $this->get_http()->request('GET', '/address_book_process.php');
        $this->assertSame(200, $new_page->getStatusCode());
        $new_html = $new_page->getContent(false);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $city = 'HTTP Alt City';

        $this->get_http()->request('POST', '/address_book_process.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => 'Fixture',
                'lastname' => 'Customer',
                'street_address' => '2 Alternate Street',
                'city' => $city,
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
            ],
        ]);

        $book = $this->get_http()->request('GET', '/address_book.php');
        $this->assertStringContainsString($city, $book->getContent(false));

        $this->extra_address_id = http_customer_fixture_sql::latest_non_primary_address_book_id();
        $this->assertNotNull($this->extra_address_id);

        $delete_page = $this->get_http()->request('GET', '/address_book_process.php', [
            'query' => [
                'delete' => (string) $this->extra_address_id,
            ],
        ]);
        $delete_html = $delete_page->getContent(false);
        $delete_formid = self::parse_hidden_input($delete_html, 'formid');
        $this->assertNotSame('', $delete_formid);

        $this->get_http()->request('POST', '/address_book_process.php', [
            'query' => [
                'delete' => (string) $this->extra_address_id,
            ],
            'body' => [
                'action' => 'deleteconfirm',
                'formid' => $delete_formid,
            ],
        ]);

        $after_delete = $this->get_http()->request('GET', '/address_book.php');
        $this->assertStringNotContainsString($city, $after_delete->getContent(false));
        $this->extra_address_id = null;
    }
}
