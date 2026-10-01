<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_comments_test extends http_test_case
{
    public function test_checkout_process_stores_customer_comment_in_order_history(): void
    {
        $comment = 'HTTP fixture checkout comment ' . uniqid('', true);

        $this->login_fixture_customer();

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
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
        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
                'comments' => $comment,
            ],
        ]);

        $final_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $final_url);

        $orders_id = http_orders_lookup::latest_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertSame($comment, http_orders_lookup::orders_status_history_comment_for_order($orders_id));
    }
}
