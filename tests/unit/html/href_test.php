<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\html;

use Href;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\DataProvider;

final class href_test extends html_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constant_if_missing('SESSION_FORCE_COOKIE_USE', 'False');
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    public function test_build_creates_page_and_parameters(): void {
        $href = Href::build('', 'products.php', ['cPath' => '1_2']);

        $this->assertSame('products.php', $href->get_page());
        $this->assertSame(['cPath' => '1_2'], $href->get_parameters());
    }

    #[DataProvider('real_link_provider')]
    public function test_real_link(
        string $page,
        array|string $parameters,
        bool $encode_separators,
        string $expected
    ): void {
        $href = new Href('', $page, $parameters);
        $href->set_separator_encoding($encode_separators);

        $this->assertSame($expected, $href->real_link());
    }

    public static function real_link_provider(): array {
        return [
            'page without parameters' => ['index.php', [], true, 'index.php'],
            'query string from array' => ['page.php', ['a' => '1', 'b' => '2'], true, 'page.php?a=1&amp;b=2'],
            'raw ampersands when encoding disabled' => ['page.php', ['a' => '1', 'b' => '2'], false, 'page.php?a=1&b=2'],
            'parses string parameters' => ['page.php', 'foo=bar&baz=qux', true, 'page.php?foo=bar&amp;baz=qux'],
            'encodes special characters in values' => ['page.php', ['q' => 'a&b'], false, 'page.php?q=a%26b'],
        ];
    }

    public function test_fluent_parameter_mutators(): void {
        $href = new Href('', 'cart.php', ['old' => '1']);
        $href->set_page('checkout.php')
            ->add_parameters(['new' => '2'])
            ->delete_parameter('old');

        $this->assertSame('checkout.php', $href->get_page());
        $this->assertSame(['new' => '2'], $href->get_parameters());
    }

    public function test_set_parameter_overwrites_named_value(): void {
        $href = new Href('', 'page.php', ['id' => '1']);
        $href->set_parameter('id', '99')->set_parameter('sort', 'name');

        $this->assertSame(['id' => '99', 'sort' => 'name'], $href->get_parameters());
    }

    public function test_separator_encoding_round_trip(): void {
        $href = new Href('', 'page.php', ['a' => '1']);
        $href->set_separator_encoding(false);

        $this->assertFalse($href->get_separator_encoding());
        $this->assertSame('page.php?a=1', $href->real_link());
    }

    public function test_retain_query_except_merges_get_parameters(): void {
        $_GET = [
            'sort' => 'name',
            'page' => '2',
            'x' => '10',
            'y' => '20',
        ];

        $href = new Href('', 'list.php', ['existing' => 'yes']);
        $href->retain_query_except(['page']);

        $this->assertSame(
            'list.php?existing=yes&amp;sort=name',
            $href->real_link()
        );
    }

    #[DataProvider('include_session_provider')]
    public function test_set_include_session(bool $requested, bool $expected): void {
        $href = new Href('', 'index.php', [], $requested);

        $this->assertSame($expected, $href->get_include_session());
    }

    public static function include_session_provider(): array {
        return [
            'enabled when cookies are not forced' => [true, true],
            'can be turned off explicitly' => [false, false],
        ];
    }

    public function test_set_include_session_can_be_disabled_explicitly(): void {
        $href = new Href('', 'index.php', [], true);
        $href->set_include_session(false);

        $this->assertFalse($href->get_include_session());
    }

    public function test_link_uses_hook_chain(): void {
        $href = new Href('', 'hooked.php', ['id' => '9']);
        $hooks = $this->create_href_hooks();
        $href->set_hooks($hooks);

        $this->assertSame('hooked.php?id=9', "$href");
        $this->assertSame('"hooked.php?id=9"', json_encode($href));
    }

    public function test_hook_static_method_builds_link_from_href_instance(): void {
        $href = new Href('', 'static.php', ['mode' => 'view']);
        $href->set_separator_encoding(false);

        $chain = Href::hook(['href' => $href]);

        $this->assertSame('static.php?mode=view', $chain['link']);
    }
}

final class href_session_force_cookie_test extends phoenix_test_case {

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_include_session_disabled_when_cookies_are_forced(): void {
        define('SESSION_FORCE_COOKIE_USE', 'True');
        define('DIR_WS_CATALOG', '/');
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $href = new Href('', 'index.php', [], true);

        $this->assertFalse($href->get_include_session());
    }
}
