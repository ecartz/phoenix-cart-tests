<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_alternate_address_test extends http_test_case
{
    private ?int $extra_address_id = null;

    protected function tearDown(): void {
        if ($this->extra_address_id !== null) {
            http_customer_fixture_sql::delete_address_book_entry($this->extra_address_id);
        }

        parent::tearDown();
    }

    public function test_checkout_uses_secondary_shipping_and_payment_address(): void {
        $this->login_fixture_customer();
        $city = 'Checkout Alt City';
        $this->insert_secondary_address($city);

        $this->extra_address_id = http_customer_fixture_sql::latest_non_primary_address_book_id();
        $this->assertNotNull($this->extra_address_id);
        $address_id = (string) $this->extra_address_id;

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_address_page = $this->get_http()->request('GET', '/checkout_shipping_address.php');
        $shipping_address_html = $shipping_address_page->getContent(false);
        $shipping_address_formid = self::parse_hidden_input($shipping_address_html, 'formid');

        $this->get_http()->request('POST', '/checkout_shipping_address.php', [
            'body' => [
                'action' => 'select',
                'formid' => $shipping_address_formid,
                'address' => $address_id,
            ],
        ]);

        $payment_address_page = $this->get_http()->request('GET', '/checkout_payment_address.php');
        $payment_address_html = $payment_address_page->getContent(false);
        $payment_address_formid = self::parse_hidden_input($payment_address_html, 'formid');

        $this->get_http()->request('POST', '/checkout_payment_address.php', [
            'body' => [
                'action' => 'select',
                'formid' => $payment_address_formid,
                'address' => $address_id,
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $shipping_html = $shipping_page->getContent(false);
        $shipping_formid = self::parse_hidden_input($shipping_html, 'formid');

        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);
        $payment_html = $payment_page->getContent(false);
        $payment_formid = self::parse_hidden_input($payment_html, 'formid');

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $confirmation_html = $confirmation_page->getContent(false);
        $this->assertStringContainsString($city, $confirmation_html);

        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');
        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $final_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $final_url);

        $this->assertSame(
            'Cash on Delivery',
            http_orders_lookup::latest_payment_method_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );
    }

    private function insert_secondary_address(string $city): void {
        $new_page = $this->get_http()->request('GET', '/address_book_process.php');
        $formid = self::parse_hidden_input($new_page->getContent(false), 'formid');
        $this->assertNotSame('', $formid);

        $this->get_http()->request('POST', '/address_book_process.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => 'Fixture',
                'lastname' => 'Customer',
                'street_address' => '9 Checkout Lane',
                'city' => $city,
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
            ],
        ]);
    }
}
