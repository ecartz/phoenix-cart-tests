<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_local_redirect_bootstrap;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[Group('http')]
final class checkout_local_redirect_test extends http_test_case
{
    protected function setUp(): void
    {
        parent::setUp();
        http_local_redirect_bootstrap::install_fixture_module();
    }

    protected function tearDown(): void
    {
        http_local_redirect_bootstrap::remove_fixture_module();
        parent::tearDown();
    }

    public function test_logged_in_customer_completes_checkout_with_local_redirect_module(): void
    {
        $confirmation_html = $this->walk_to_local_redirect_confirmation();

        $this->assertStringContainsString(
            'ext/modules/payment/http_local_redirect/return.php',
            $confirmation_html
        );

        $token = self::parse_hidden_input($confirmation_html, http_local_redirect_bootstrap::TOKEN_INPUT_NAME);
        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');

        $ext_return = $this->get_http_without_redirects()->request(
            'POST',
            http_local_redirect_bootstrap::ext_return_path(),
            [
                'body' => [
                    http_local_redirect_bootstrap::TOKEN_INPUT_NAME => $token,
                ],
            ]
        );
        $this->assertSame(302, $ext_return->getStatusCode());
        $this->assertStringContainsString(
            'checkout_process.php',
            self::response_location($ext_return)
        );

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
                http_local_redirect_bootstrap::TOKEN_INPUT_NAME => http_local_redirect_bootstrap::TOKEN_VALUE,
            ],
        ]);

        $final_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $final_url);

        $success_body = $success->getContent(false);
        $this->assertStringContainsString('cm-cs-thank-you', $success_body);

        $this->assertSame(
            http_local_redirect_bootstrap::PAYMENT_METHOD_TITLE,
            http_orders_lookup::latest_payment_method_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );
    }

    public function test_bad_token_on_checkout_process_redirects_without_local_redirect_order(): void
    {
        $confirmation_html = $this->walk_to_local_redirect_confirmation();
        $confirm_formid = self::parse_hidden_input($confirmation_html, 'formid');
        $orders_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

        $rejected = $this->get_http_without_redirects()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
                http_local_redirect_bootstrap::TOKEN_INPUT_NAME => 'not-the-fixture-token',
            ],
        ]);

        $this->assertSame(302, $rejected->getStatusCode());
        $location = self::response_location($rejected);
        $this->assertStringContainsString('checkout_payment.php', $location);
        $this->assertStringContainsString('payment_error=http_local_redirect', $location);

        $this->assertSame(
            $orders_before,
            http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );
    }

    public function test_bad_token_on_ext_return_redirects_without_local_redirect_order(): void
    {
        $this->walk_to_local_redirect_confirmation();
        $orders_before = http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);

        $rejected = $this->get_http_without_redirects()->request(
            'POST',
            http_local_redirect_bootstrap::ext_return_path(),
            [
                'body' => [
                    http_local_redirect_bootstrap::TOKEN_INPUT_NAME => 'not-the-fixture-token',
                ],
            ]
        );

        $this->assertSame(302, $rejected->getStatusCode());
        $location = self::response_location($rejected);
        $this->assertStringContainsString('checkout_payment.php', $location);
        $this->assertStringContainsString('payment_error=http_local_redirect', $location);

        $this->assertSame(
            $orders_before,
            http_orders_lookup::max_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL)
        );
    }

    private function walk_to_local_redirect_confirmation(): string
    {
        $this->login_fixture_customer();

        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

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
        $this->assertStringContainsString(http_local_redirect_bootstrap::PAYMENT_METHOD_TITLE, $payment_html);

        $payment_formid = self::parse_hidden_input($payment_html, 'formid');
        $this->assertNotSame('', $payment_formid);

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => http_local_redirect_bootstrap::MODULE_CODE,
            ],
        ]);
        $this->assertSame(200, $confirmation_page->getStatusCode());
        $confirmation_html = $confirmation_page->getContent(false);
        $this->assertStringContainsString(http_local_redirect_bootstrap::PAYMENT_METHOD_TITLE, $confirmation_html);

        $token = self::parse_hidden_input($confirmation_html, http_local_redirect_bootstrap::TOKEN_INPUT_NAME);
        $this->assertSame(http_local_redirect_bootstrap::TOKEN_VALUE, $token);

        return $confirmation_html;
    }

    private static function response_location(ResponseInterface $response): string
    {
        $headers = $response->getHeaders(false);

        return isset($headers['location'][0]) ? (string) $headers['location'][0] : '';
    }
}
