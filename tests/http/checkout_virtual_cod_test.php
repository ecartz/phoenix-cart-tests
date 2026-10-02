<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_virtual_cod_test extends http_test_case
{
    protected function setUp(): void
    {
        parent::setUp();
        http_checkout_fixture_sql::insert_virtual_download_for_pears();
    }

    protected function tearDown(): void
    {
        http_checkout_fixture_sql::remove_virtual_download_for_pears();
        parent::tearDown();
    }

    public function test_virtual_download_cart_skips_shipping_and_hides_cod(): void
    {
        $this->login_fixture_customer();

        $option_id = http_checkout_fixture_sql::virtual_option_id();
        $value_id = http_checkout_fixture_sql::virtual_value_id();

        $this->post_add_product_to_cart(3, [
            'id[' . $option_id . ']' => (string) $value_id,
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $this->assertSame(200, $shipping_page->getStatusCode());
        $final_url = (string) ($shipping_page->getInfo('url') ?? '');
        if (str_contains($final_url, 'checkout_payment.php')) {
            $payment_page = $shipping_page;
        } else {
            $this->assertStringContainsString('checkout_shipping.php', $final_url);
            $payment_page = $this->get_http()->request('GET', '/checkout_payment.php');
            $this->assertSame(200, $payment_page->getStatusCode());
        }

        $payment_html = $payment_page->getContent(false);
        $this->assertStringNotContainsString('Cash on Delivery', $payment_html);
        $this->assertStringContainsString('Check/Money Order', $payment_html);

        $payment_formid = self::parse_hidden_input($payment_html, 'formid');
        $this->assertNotSame('', $payment_formid);

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'moneyorder',
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

        $success_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $success_url);

        $this->assertSame(
            'Check/Money Order',
            http_orders_lookup::latest_payment_method_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );
    }
}
