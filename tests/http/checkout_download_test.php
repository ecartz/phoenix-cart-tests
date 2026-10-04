<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_admin_fixture;
use PhoenixCart\Tests\support\http_bootstrap;
use PhoenixCart\Tests\support\http_checkout_fixture_sql;
use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[Group('http')]
final class checkout_download_test extends http_test_case {

    private const DOWNLOAD_FILENAME = 'http-test-download.zip';

    private const DOWNLOAD_PAYLOAD = 'phoenix-http-download-fixture-payload';

    private ?string $download_path = null;

    protected function setUp(): void {
        parent::setUp();
        http_checkout_fixture_sql::insert_virtual_download_for_pears();
        $this->write_download_file();
    }

    protected function tearDown(): void {
        http_admin_fixture::cleanup();
        $this->remove_download_file();
        http_checkout_fixture_sql::remove_virtual_download_for_pears();
        parent::tearDown();
    }

    public function test_download_respects_pending_status_maxcount_and_maxdays(): void {
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
        $this->assertSame(1, http_orders_lookup::orders_status_id_for_order($orders_id));

        $download_id = http_orders_lookup::orders_products_download_id_for_order($orders_id);
        $this->assertNotNull($download_id);
        $this->assertSame(5, http_orders_lookup::orders_products_download_count($download_id));

        $pending = $this->request_download($orders_id, $download_id);
        $this->assertSame('', $pending->getContent(false));
        $this->assertSame(5, http_orders_lookup::orders_products_download_count($download_id));

        $this->set_order_status_through_admin($orders_id, 2);
        $this->assertSame(2, http_orders_lookup::orders_status_id_for_order($orders_id));

        $download = $this->request_download($orders_id, $download_id);
        $this->assertSame(200, $download->getStatusCode());
        $this->assertSame(self::DOWNLOAD_PAYLOAD, $download->getContent());
        $this->assertSame(4, http_orders_lookup::orders_products_download_count($download_id));

        http_orders_lookup::set_order_purchased_days_ago($orders_id, 30);
        $expired = $this->request_download($orders_id, $download_id);
        $this->assertSame('', $expired->getContent(false));
        $this->assertSame(4, http_orders_lookup::orders_products_download_count($download_id));

        http_orders_lookup::set_download_maxdays($download_id, 0);
        $unlimited = $this->request_download($orders_id, $download_id);
        $this->assertSame(self::DOWNLOAD_PAYLOAD, $unlimited->getContent());
        $this->assertSame(3, http_orders_lookup::orders_products_download_count($download_id));
    }

    private function request_download(int $orders_id, int $download_id): ResponseInterface {
        return $this->get_http()->request('GET', '/download.php', [
            'query' => [
                'order' => (string) $orders_id,
                'id' => (string) $download_id,
            ],
        ]);
    }

    private function set_order_status_through_admin(int $orders_id, int $status_id): void {
        $admin_http = http_admin_fixture::login();

        $edit_page = $admin_http->request('GET', '/admin/orders.php', [
            'query' => [
                'oID' => (string) $orders_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $edit_page->getStatusCode());
        $edit_html = $edit_page->getContent(false);
        $this->assertStringNotContainsString('login.php', (string) ($edit_page->getInfo('url') ?? ''));
        $formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $formid);

        $update = $admin_http->request('POST', '/admin/orders.php', [
            'query' => [
                'oID' => (string) $orders_id,
                'action' => 'update_order',
            ],
            'body' => [
                'formid' => $formid,
                'status' => (string) $status_id,
                'comments' => 'HTTP processing',
            ],
        ]);
        $this->assertContains($update->getStatusCode(), [200, 302]);
    }

    private function write_download_file(): void {
        $directory = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'download';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create download directory: ' . $directory);
        }

        $this->download_path = $directory . DIRECTORY_SEPARATOR . self::DOWNLOAD_FILENAME;
        if (file_put_contents($this->download_path, self::DOWNLOAD_PAYLOAD) === false) {
            throw new \RuntimeException('Cannot write download fixture file: ' . $this->download_path);
        }
    }

    private function remove_download_file(): void {
        if ($this->download_path !== null && is_file($this->download_path)) {
            unlink($this->download_path);
        }

        $this->download_path = null;
    }

}
