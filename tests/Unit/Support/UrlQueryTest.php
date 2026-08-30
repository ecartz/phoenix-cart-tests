<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use PhoenixCart\Tests\Support\PhoenixTestCase;
use url_query;

final class UrlQueryTest extends PhoenixTestCase
{
    /**
     * @dataProvider parseProvider
     *
     * @param array<string, mixed> $expected
     */
    public function testParse(string $query, array $expected): void
    {
        $this->assertSame($expected, url_query::parse($query));
    }

    public static function parseProvider(): array
    {
        return [
            'empty query' => ['', []],
            'single parameter' => ['foo=bar', ['foo' => 'bar']],
            'multiple parameters' => ['a=1&b=2', ['a' => '1', 'b' => '2']],
            'nested array keys' => ['items[0]=x&items[1]=y', ['items' => ['x', 'y']]],
            'url encoded values' => ['q=hello%20world', ['q' => 'hello world']],
        ];
    }
}
