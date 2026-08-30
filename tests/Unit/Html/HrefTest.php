<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use Href;

final class HrefTest extends HtmlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->defineConstantIfMissing('SESSION_FORCE_COOKIE_USE', 'False');
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    public function testBuildCreatesPageAndParameters(): void
    {
        $href = Href::build('', 'products.php', ['cPath' => '1_2']);

        $this->assertSame('products.php', $href->get_page());
        $this->assertSame(['cPath' => '1_2'], $href->get_parameters());
    }

    /**
     * @dataProvider realLinkProvider
     */
    public function testRealLink(
        string $page,
        array|string $parameters,
        bool $encodeSeparators,
        string $expected
    ): void {
        $href = new Href('', $page, $parameters);
        $href->set_separator_encoding($encodeSeparators);

        $this->assertSame($expected, $href->real_link());
    }

    public static function realLinkProvider(): array
    {
        return [
            'page without parameters' => ['index.php', [], true, 'index.php'],
            'query string from array' => ['page.php', ['a' => '1', 'b' => '2'], true, 'page.php?a=1&amp;b=2'],
            'raw ampersands when encoding disabled' => ['page.php', ['a' => '1', 'b' => '2'], false, 'page.php?a=1&b=2'],
            'parses string parameters' => ['page.php', 'foo=bar&baz=qux', true, 'page.php?foo=bar&amp;baz=qux'],
            'encodes special characters in values' => ['page.php', ['q' => 'a&b'], false, 'page.php?q=a%26b'],
        ];
    }

    public function testFluentParameterMutators(): void
    {
        $href = new Href('', 'cart.php', ['old' => '1']);
        $href->set_page('checkout.php')
            ->add_parameters(['new' => '2'])
            ->delete_parameter('old');

        $this->assertSame('checkout.php', $href->get_page());
        $this->assertSame(['new' => '2'], $href->get_parameters());
    }

    public function testRetainQueryExceptMergesGetParameters(): void
    {
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

    /**
     * @dataProvider includeSessionProvider
     */
    public function testSetIncludeSession(bool $requested, bool $expected): void
    {
        $href = new Href('', 'index.php', [], $requested);

        $this->assertSame($expected, $href->get_include_session());
    }

    public static function includeSessionProvider(): array
    {
        return [
            'enabled when cookies are not forced' => [true, true],
            'can be turned off explicitly' => [false, false],
        ];
    }

    public function testSetIncludeSessionCanBeDisabledExplicitly(): void
    {
        $href = new Href('', 'index.php', [], true);
        $href->set_include_session(false);

        $this->assertFalse($href->get_include_session());
    }

    public function testLinkUsesHookChain(): void
    {
        $href = new Href('', 'hooked.php', ['id' => '9']);
        $hooks = $this->createHrefHooks();
        $href->set_hooks($hooks);

        $this->assertSame('hooked.php?id=9', (string) $href);
        $this->assertSame('"hooked.php?id=9"', json_encode($href));
    }

    public function testHookStaticMethodBuildsLinkFromHrefInstance(): void
    {
        $href = new Href('', 'static.php', ['mode' => 'view']);
        $href->set_separator_encoding(false);

        $chain = Href::hook(['href' => $href]);

        $this->assertSame('static.php?mode=view', $chain['link']);
    }
}
