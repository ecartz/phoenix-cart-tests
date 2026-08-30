<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit;

use PhoenixCart\Tests\Support\PhoenixTestCase;
use Text;

final class TextTest extends PhoenixTestCase
{
    /**
     * @dataProvider breakProvider
     */
    public function testBreak(string $input, int $maximum, string $marker, string $expected): void
    {
        $this->assertSame($expected, Text::break($input, $maximum, $marker));
    }

    public static function breakProvider(): array
    {
        return [
            'short word unchanged' => ['hello', 10, '-', 'hello '],
            'long word split' => ['abcdefghij', 4, '-', 'abcd-efgh-ij '],
            'multiple words' => ['ab cd', 2, '-', 'ab cd '],
        ];
    }

    /**
     * @dataProvider inputProvider
     */
    public function testInput(string $input, string $expected): void
    {
        $this->assertSame($expected, Text::input($input));
    }

    public static function inputProvider(): array
    {
        return [
            'trims whitespace' => ['  value  ', 'value'],
            'sanitizes angle brackets' => ['<script>', '_script_'],
            'collapses spaces' => ['a   b', 'a b'],
        ];
    }

    /**
     * @dataProvider isEmptyProvider
     */
    public function testIsEmpty(?string $value, bool $expected): void
    {
        $this->assertSame($expected, Text::is_empty($value));
    }

    public static function isEmptyProvider(): array
    {
        return [
            'null is empty' => [null, true],
            'blank string is empty' => ['   ', true],
            'non-empty string' => ['x', false],
        ];
    }

    public function testPrefixAndSuffixHelpers(): void
    {
        $this->assertTrue(Text::is_prefixed_by('prefix-value', 'prefix-'));
        $this->assertFalse(Text::is_prefixed_by('value', 'prefix-'));
        $this->assertTrue(Text::is_suffixed_by('value-suffix', '-suffix'));
        $this->assertFalse(Text::is_suffixed_by('value', '-suffix'));
    }

    public function testTrimOnceHelpers(): void
    {
        $this->assertSame('value', Text::ltrim_once('prefix-value', 'prefix-'));
        $this->assertSame('prefix-value', Text::ltrim_once('prefix-value', 'missing-'));
        $this->assertSame('value', Text::rtrim_once('value-suffix', '-suffix'));
        $this->assertSame('value-suffix', Text::rtrim_once('value-suffix', '-missing'));
    }

    public function testOutputEscapesQuotes(): void
    {
        $this->assertSame('&quot;quoted&quot;', Text::output('"quoted"'));
    }

    public function testSanitizeReplacesUnsafeCharacters(): void
    {
        $this->assertSame('_tag_', Text::sanitize('<tag>'));
        $this->assertSame('a b', Text::sanitize('a   b'));
    }
}
