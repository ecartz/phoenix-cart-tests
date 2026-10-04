<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_mail_capture;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_moneyorder_test extends http_test_case {

    private const EXPECTED_SUBTOTAL = 4.99;

    private const EXPECTED_SHIPPING = 5.0;

    private const EXPECTED_TAX = 0.3493;

    private const EXPECTED_TOTAL = 10.3393;

    public function test_logged_in_customer_completes_checkout_with_money_order(): void {
        $this->login_fixture_customer();
        $orders_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $this->assertSame(200, $shipping_page->getStatusCode());
        $shipping_html = $shipping_page->getContent(false);
        $this->assertStringContainsString('Fixture', $shipping_html);
        $this->assertStringContainsString('Flat Rate', $shipping_html);

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
        $this->assertStringContainsString('Pears', $confirmation_html);
        $this->assertStringContainsString('Check/Money Order', $confirmation_html);
        $this->assertStringContainsString('Your Store', $confirmation_html);

        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');
        $this->assertNotSame('', $confirm_formid);

        if (http_mail_capture::is_enabled()) {
            http_mail_capture::clear();
        }

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $final_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $final_url);

        $success_body = $success->getContent(false);
        $this->assertStringContainsString('cm-cs-thank-you', $success_body);

        $orders_after = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan($orders_before, $orders_after);
        $this->assertSame(
            'Check/Money Order',
            http_orders_lookup::payment_method_for_order($orders_after)
        );

        $this->assertSame(
            self::EXPECTED_SUBTOTAL,
            http_orders_lookup::orders_total_value_for_order($orders_after, 'ot_subtotal')
        );
        $this->assertSame(
            self::EXPECTED_SHIPPING,
            http_orders_lookup::orders_total_value_for_order($orders_after, 'ot_shipping')
        );
        $this->assertSame(
            self::EXPECTED_TAX,
            http_orders_lookup::orders_total_value_for_order($orders_after, 'ot_tax')
        );
        $this->assertSame(
            self::EXPECTED_TOTAL,
            http_orders_lookup::orders_total_value_for_order($orders_after, 'ot_total')
        );

        if (http_mail_capture::is_enabled()) {
            $mail = http_mail_capture::read_combined();
            $this->assertStringContainsString('Order Process', $mail);
            $this->assertStringContainsString('Order Number: ' . $orders_after, $mail);
            $this->assertStringContainsString('Pears', $mail);
            $this->assertStringContainsString('Check/Money Order', $mail);
            $this->assertStringContainsString(self::FIXTURE_CUSTOMER_EMAIL, $mail);
        }
    }

}
