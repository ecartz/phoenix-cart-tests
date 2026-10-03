<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use PhoenixCart\Tests\support\phoenix_test_case;
use ReflectionClass;
use Request;
use PHPUnit\Framework\Attributes\DataProvider;

final class request_test extends phoenix_test_case
{
    protected function setUp(): void {
        parent::setUp();

        $this->reset_cached_page();
    }

    protected function tearDown(): void {
        $_GET = [];
        $_POST = [];
        $_SERVER = array_diff_key($_SERVER, array_flip([
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
            'SCRIPT_NAME',
        ]));

        putenv('HTTPS');

        $this->reset_cached_page();

        parent::tearDown();
    }

    #[DataProvider('value_provider')]
    public function test_value(string $name, array $get, array $post, ?string $expected): void {
        $_GET = $get;
        $_POST = $post;

        $this->assertSame($expected, Request::value($name));
    }

    public static function value_provider(): array {
        return [
            'prefers get over post' => ['id', ['id' => 'from-get'], ['id' => 'from-post'], 'from-get'],
            'falls back to post' => ['mode', [], ['mode' => 'view'], 'view'],
            'missing returns null' => ['missing', [], [], null],
        ];
    }

    public function test_get_ip_returns_first_valid_ipv4(): void {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9, 198.51.100.2';
        $_SERVER['REMOTE_ADDR'] = '192.0.2.1';

        $this->assertSame('198.51.100.2', Request::get_ip());
    }

    public function test_get_ip_falls_back_to_remote_addr(): void {
        $_SERVER['REMOTE_ADDR'] = '192.0.2.55';

        $this->assertSame('192.0.2.55', Request::get_ip());
    }

    public function test_get_ip_returns_false_when_no_valid_address(): void {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'not-an-ip';
        $_SERVER['REMOTE_ADDR'] = 'also-invalid';

        $this->assertFalse(Request::get_ip());
    }

    public function test_get_page_strips_catalog_prefix_from_script_name(): void {
        $_SERVER['SCRIPT_NAME'] = '/shop/products.php';

        $this->assertSame('products.php', Request::get_page('/shop/'));
    }

    public function test_get_page_caches_resolved_value(): void {
        $_SERVER['SCRIPT_NAME'] = '/shop/first.php';
        $this->assertSame('first.php', Request::get_page('/shop/'));

        $_SERVER['SCRIPT_NAME'] = '/shop/second.php';
        $this->assertSame('first.php', Request::get_page('/shop/'));
    }

    public function test_is_ssl_reads_https_environment(): void {
        putenv('HTTPS=on');
        $this->assertTrue(Request::is_ssl());

        putenv('HTTPS=off');
        $this->assertFalse(Request::is_ssl());
    }

    private function reset_cached_page(): void {
        $reflection = new ReflectionClass(Request::class);
        $property = $reflection->getProperty('page');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
