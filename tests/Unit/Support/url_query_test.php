<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use PhoenixCart\Tests\Support\phoenix_test_case;
use url_query;
use PHPUnit\Framework\Attributes\DataProvider;

final class url_query_test extends phoenix_test_case
{
    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('parse_provider')]
    public function test_parse(string $query, array $expected): void
    {
        $this->assertSame($expected, url_query::parse($query));
    }

    public static function parse_provider(): array
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
