<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class address_book_test extends http_test_case {

    private ?int $extra_address_id = null;

    protected function tearDown(): void {
        if ($this->extra_address_id !== null) {
            http_customer_fixture_sql::delete_address_book_entry($this->extra_address_id);
        }
        http_customer_fixture_sql::restore_fixture_default_address();

        parent::tearDown();
    }

    public function test_customer_can_add_and_delete_secondary_address(): void {
        $this->login_fixture_customer();

        $new_page = $this->get_http()->request('GET', '/address_book_process.php');
        $this->assertSame(200, $new_page->getStatusCode());
        $new_html = $new_page->getContent(false);
        $formid = self::parse_formid_from_page($new_html);
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
        $delete_formid = self::parse_formid_from_page($delete_html);
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

    public function test_customer_can_edit_address_set_primary_and_cannot_delete_primary(): void {
        $this->login_fixture_customer();

        $new_page = $this->get_http()->request('GET', '/address_book_process.php');
        $formid = self::parse_formid_from_page($new_page->getContent(false));
        $city = 'HTTP Secondary City';
        $this->get_http()->request('POST', '/address_book_process.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => 'Fixture',
                'lastname' => 'Customer',
                'street_address' => '3 Alternate Street',
                'city' => $city,
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
            ],
        ]);

        $this->extra_address_id = http_customer_fixture_sql::latest_non_primary_address_book_id();
        $this->assertNotNull($this->extra_address_id);

        $edit_page = $this->get_http()->request('GET', '/address_book_process.php', [
            'query' => [
                'edit' => (string) $this->extra_address_id,
            ],
        ]);
        $edit_formid = self::parse_formid_from_page($edit_page->getContent(false));
        $updated_city = 'HTTP Edited Secondary';
        $this->get_http()->request('POST', '/address_book_process.php', [
            'query' => [
                'edit' => (string) $this->extra_address_id,
            ],
            'body' => [
                'action' => 'update',
                'formid' => $edit_formid,
                'firstname' => 'Fixture',
                'lastname' => 'Customer',
                'street_address' => '3 Alternate Street',
                'city' => $updated_city,
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
                'primary' => 'on',
            ],
        ]);

        $default_id = http_customer_fixture_sql::default_address_book_id_for_fixture();
        $this->assertSame($this->extra_address_id, $default_id);

        $this->get_http()->request('GET', '/address_book_process.php', [
            'query' => [
                'delete' => (string) $default_id,
            ],
        ]);
        $after_primary_delete = $this->get_http()->request('GET', '/address_book.php');
        $after_body = $after_primary_delete->getContent(false);
        $this->assertStringContainsString($updated_city, $after_body);
        $this->assertSame($default_id, http_customer_fixture_sql::default_address_book_id_for_fixture());
    }

}
