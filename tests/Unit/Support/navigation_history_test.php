<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use hooks;
use Linker;
use navigationHistory;
use PhoenixCart\Tests\Support\phoenix_test_case;
use ReflectionClass;
use Request;

final class navigation_history_test extends phoenix_test_case
{
    private navigationHistory $history;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('HTTP_SERVER')) {
            define('HTTP_SERVER', 'https://shop.example.com');
        }
        if (!defined('DIR_WS_CATALOG')) {
            define('DIR_WS_CATALOG', '/');
        }
        if (!defined('SESSION_FORCE_COOKIE_USE')) {
            define('SESSION_FORCE_COOKIE_USE', 'False');
        }

        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_GET = [];
        $_POST = [];
        $GLOBALS['all_hooks'] = new hooks('shop');
        $GLOBALS['Linker'] = new Linker('https://shop.example.com/');

        $this->reset_cached_page();
        $this->history = new navigationHistory();
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        unset($GLOBALS['cPath']);
        $this->reset_cached_page();

        parent::tearDown();
    }

    public function test_reset_clears_path_and_snapshot(): void
    {
        $this->history->path = [['page' => 'cart.php']];
        $this->history->snapshot = ['page' => 'cart.php'];
        $this->history->reset();

        $this->assertSame([], $this->history->path);
        $this->assertSame([], $this->history->snapshot);
    }

    public function test_set_snapshot_from_explicit_page_filters_sensitive_keys(): void
    {
        $this->history->set_snapshot([
            'page' => 'checkout.php',
            'get' => [
                'cPath' => '1',
                'password' => 'secret',
                'token_nh-dns' => '1',
            ],
            'post' => [
                'password_confirm' => 'secret',
                'qty' => '2',
            ],
        ]);

        $this->assertSame('checkout.php', $this->history->snapshot['page']);
        $this->assertSame(['cPath' => '1'], $this->history->snapshot['get']);
        $this->assertSame(['qty' => '2'], $this->history->snapshot['post']);
    }

    public function test_add_current_page_records_filtered_request(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/products.php';
        $_GET = ['id' => '9', 'password' => 'x'];
        $_POST = ['qty' => '1'];
        $this->reset_cached_page();

        $this->history->add_current_page();

        $this->assertCount(1, $this->history->path);
        $this->assertSame('products.php', $this->history->path[0]['page']);
        $this->assertSame(['id' => '9'], $this->history->path[0]['get']);
        $this->assertSame(['qty' => '1'], $this->history->path[0]['post']);
    }

    public function test_pop_snapshot_as_link_defaults_to_index_when_empty(): void
    {
        $link = $this->history->pop_snapshot_as_link();

        $this->assertStringContainsString('index.php', "$link");
        $this->assertSame([], $this->history->snapshot);
    }

    public function test_pop_snapshot_as_link_returns_and_clears_snapshot(): void
    {
        $this->history->set_snapshot([
            'page' => 'account.php',
            'get' => ['edit' => '1'],
        ]);

        $link = $this->history->pop_snapshot_as_link();

        $this->assertStringContainsString('account.php', "$link");
        $this->assertStringContainsString('edit=1', "$link");
        $this->assertSame([], $this->history->snapshot);
    }

    public function test_set_path_as_snapshot_uses_history_entry(): void
    {
        $this->history->path = [
            [
                'page' => 'cart.php',
                'get' => ['action' => 'buy'],
                'post' => [],
            ],
            [
                'page' => 'checkout.php',
                'get' => [],
                'post' => [],
            ],
        ];

        $this->history->set_path_as_snapshot(1);

        $this->assertSame('cart.php', $this->history->snapshot['page']);
        $this->assertSame(['action' => 'buy'], $this->history->snapshot['get']);
    }

    private function reset_cached_page(): void
    {
        $reflection = new ReflectionClass(Request::class);
        $property = $reflection->getProperty('page');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
