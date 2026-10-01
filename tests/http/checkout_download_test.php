<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_bootstrap;
use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class checkout_download_test extends http_test_case
{
    private const DOWNLOAD_FILENAME = 'http-test-download.zip';

    private const DOWNLOAD_PAYLOAD = 'phoenix-http-download-fixture-payload';

    private ?string $download_path = null;

    protected function setUp(): void
    {
        parent::setUp();
        http_checkout_fixture_sql::insert_virtual_download_for_pears();
        $this->write_download_file();
    }

    protected function tearDown(): void
    {
        $this->remove_download_file();
        http_checkout_fixture_sql::remove_virtual_download_for_pears();
        parent::tearDown();
    }

    public function test_processing_order_allows_logged_in_download(): void
    {
        $this->login_fixture_customer();

        $option_id = http_checkout_fixture_sql::virtual_option_id();
        $value_id = http_checkout_fixture_sql::virtual_value_id();

        $this->post_add_product_to_cart(3, [
            'id[' . $option_id . ']' => (string) $value_id,
        ]);

        $payment_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $payment_html = $payment_page->getContent(false);
        $payment_formid = self::parse_hidden_input($payment_html, 'formid');

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'moneyorder',
            ],
        ]);
        $confirm_formid = self::parse_hidden_input($confirmation_page->getContent(false), 'formid');

        $success = $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);
        $this->assertStringContainsString('checkout_success.php', (string) ($success->getInfo('url') ?? ''));

        $orders_id = http_orders_lookup::latest_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        http_orders_lookup::set_order_status($orders_id, 2);

        $download_id = http_orders_lookup::orders_products_download_id_for_order($orders_id);
        $this->assertNotNull($download_id);

        $download = $this->get_http()->request('GET', '/download.php', [
            'query' => [
                'order' => (string) $orders_id,
                'id' => (string) $download_id,
            ],
        ]);

        $this->assertSame(200, $download->getStatusCode());
        $this->assertSame(self::DOWNLOAD_PAYLOAD, $download->getContent());
    }

    private function write_download_file(): void
    {
        $directory = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'download';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create download directory: ' . $directory);
        }

        $this->download_path = $directory . DIRECTORY_SEPARATOR . self::DOWNLOAD_FILENAME;
        if (file_put_contents($this->download_path, self::DOWNLOAD_PAYLOAD) === false) {
            throw new \RuntimeException('Cannot write download fixture file: ' . $this->download_path);
        }
    }

    private function remove_download_file(): void
    {
        if ($this->download_path !== null && is_file($this->download_path)) {
            unlink($this->download_path);
        }

        $this->download_path = null;
    }
}
