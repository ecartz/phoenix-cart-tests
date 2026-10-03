<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class address_book_test extends http_test_case {

    private ?int $extra_address_id = null;

    private bool $restore_default_address = false;

    protected function tearDown(): void {
        if ($this->restore_default_address) {
            http_customer_fixture_sql::restore_fixture_default_address();
        }

        if ($this->extra_address_id !== null) {
            http_customer_fixture_sql::delete_address_book_entry($this->extra_address_id);
        }

        parent::tearDown();
    }

    public function test_customer_cannot_delete_primary_address(): void {
        $this->login_fixture_customer();

        $blocked = $this->get_http_without_redirects()->request('GET', '/address_book_process.php', [
            'query' => [
                'delete' => '1',
            ],
        ]);
        $this->assertSame(302, $blocked->getStatusCode());
        $location = $blocked->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString('address_book.php', $location);

        $book = $this->get_http()->request('GET', '/address_book.php');
        $this->assertStringContainsString('primary address cannot be deleted', $book->getContent(false));
    }

    public function test_customer_can_set_secondary_address_as_primary(): void {
        $this->login_fixture_customer();

        $new_page = $this->get_http()->request('GET', '/address_book_process.php');
        $formid = self::parse_formid_from_page($new_page->getContent(false));
        $city = 'HTTP Primary City';

        $this->get_http()->request('POST', '/address_book_process.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => 'Fixture',
                'lastname' => 'Customer',
                'street_address' => '3 Primary Street',
                'city' => $city,
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
                'primary' => 'on',
            ],
        ]);

        $this->restore_default_address = true;
        $book = $this->get_http()->request('GET', '/address_book.php');
        $this->assertStringContainsString($city, $book->getContent(false));

        $this->extra_address_id = http_customer_fixture_sql::address_book_id_for_entry_city($city);
        $this->assertNotNull($this->extra_address_id);
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

        $edit_page = $this->get_http()->request('GET', '/address_book_process.php', [
            'query' => [
                'edit' => (string) $this->extra_address_id,
            ],
        ]);
        $edit_html = $edit_page->getContent(false);
        $edit_formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $edit_formid);
        $updated_city = 'HTTP Edited City';

        $this->get_http()->request('POST', '/address_book_process.php', [
            'query' => [
                'edit' => (string) $this->extra_address_id,
            ],
            'body' => [
                'action' => 'update',
                'formid' => $edit_formid,
                'firstname' => 'Fixture',
                'lastname' => 'Customer',
                'street_address' => '2 Alternate Street',
                'city' => $updated_city,
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
            ],
        ]);

        $after_edit = $this->get_http()->request('GET', '/address_book.php');
        $this->assertStringContainsString($updated_city, $after_edit->getContent(false));

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

}
