<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_new_account_test extends http_test_case
{
    public function test_logged_out_checkout_creates_account_and_completes_cod(): void
    {
        $email = 'phoenix-http-new-checkout-' . uniqid('', true) . '@example.com';
        $password = 'phoenix-test';

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_entry = $this->get_http()->request('GET', '/checkout_shipping.php');
        $entry_url = (string) ($shipping_entry->getInfo('url') ?? '');
        $this->assertStringContainsString('create_account.php', $entry_url);

        $create_html = $shipping_entry->getContent(false);
        $create_formid = self::parse_hidden_input($create_html, 'formid');
        $this->assertNotSame('', $create_formid);

        $registered = $this->get_http()->request('POST', '/create_account.php', [
            'body' => [
                'action' => 'process',
                'formid' => $create_formid,
                'firstname' => 'Checkout',
                'lastname' => 'Newbie',
                'email_address' => $email,
                'password' => $password,
                'street_address' => '1 Test Street',
                'city' => 'Testville',
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
                'telephone' => '555-0100',
                'newsletter' => '1',
                'matc' => '1',
            ],
        ]);
        $this->assertContains($registered->getStatusCode(), [200, 302]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $this->assertSame(200, $shipping_page->getStatusCode());
        $shipping_html = $shipping_page->getContent(false);

        $shipping_formid = self::parse_hidden_input($shipping_html, 'formid');
        $this->assertNotSame('', $shipping_formid);

        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);
        $this->assertSame(200, $payment_page->getStatusCode());
        $payment_html = $payment_page->getContent(false);
        $this->assertStringContainsString('Cash on Delivery', $payment_html);

        $payment_formid = self::parse_hidden_input($payment_html, 'formid');
        $this->assertNotSame('', $payment_formid);

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $this->assertSame(200, $confirmation_page->getStatusCode());
        $confirmation_html = $confirmation_page->getContent(false);
        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');
        $this->assertNotSame('', $confirm_formid);

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $final_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $final_url);

        $this->assertSame(
            'Cash on Delivery',
            http_orders_lookup::latest_payment_method_for_email($email)
        );
    }
}
