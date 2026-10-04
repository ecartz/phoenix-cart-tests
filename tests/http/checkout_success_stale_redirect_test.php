<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_bootstrap;
use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_order_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_success_stale_redirect_test extends http_test_case {

    private const REDIRECT_MINUTES = 30;

    protected function setUp(): void {
        parent::setUp();
        http_checkout_fixture_sql::enable_checkout_success_redirect_old_order_minutes(self::REDIRECT_MINUTES);
    }

    protected function tearDown(): void {
        http_order_fixture_sql::restore_remembered_order_date_purchased();
        http_checkout_fixture_sql::restore_checkout_success_redirect_old_order_minutes();
        parent::tearDown();
    }

    public function test_stale_checkout_success_redirects_to_account_after_cod(): void {
        $this->login_fixture_customer();
        $this->complete_cod_checkout_for_pears();

        $orders_id = http_orders_lookup::latest_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan(0, $orders_id);

        http_order_fixture_sql::remember_and_age_orders_for_email(
            self::FIXTURE_CUSTOMER_EMAIL,
            self::REDIRECT_MINUTES + 5
        );

        $response = $this->get_http_without_redirects()->request('GET', '/checkout_success.php');
        $this->assertSame(302, $response->getStatusCode());

        $location = $response->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString(
            self::expected_stale_success_redirect_script(),
            $location
        );
    }

    private function complete_cod_checkout_for_pears(): void {
        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $shipping_formid = self::parse_hidden_input($shipping_page->getContent(false), 'formid');
        $this->assertNotSame('', $shipping_formid);

        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);
        $payment_formid = self::parse_hidden_input($payment_page->getContent(false), 'formid');
        $this->assertNotSame('', $payment_formid);

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $confirm_formid = self::parse_hidden_input($confirmation_page->getContent(false), 'formid');
        $this->assertNotSame('', $confirm_formid);

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);

        $this->assertStringContainsString('checkout_success.php', (string) ($success->getInfo('url') ?? ''));
    }

    private static function expected_stale_success_redirect_script(): string {
        $module_path = http_bootstrap::catalog_root()
            . '/includes/modules/content/checkout_success/cm_cs_redirect_old_order.php';

        $source = file_get_contents($module_path);
        if ($source === false) {
            throw new \RuntimeException('Cannot read checkout success redirect module: ' . $module_path);
        }

        if (preg_match("/build\\('([^']+)'\\)/", $source, $matches) !== 1) {
            throw new \RuntimeException('Cannot parse redirect target from: ' . $module_path);
        }

        return $matches[1];
    }

}
