<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit;

use PhoenixCart\Tests\support\phoenix_test_case;
use Text;
use PHPUnit\Framework\Attributes\DataProvider;

final class text_test extends phoenix_test_case {

    #[DataProvider('break_provider')]
    public function test_break(string $input, int $maximum, string $marker, string $expected): void {
        $this->assertSame($expected, Text::break($input, $maximum, $marker));
    }

    public static function break_provider(): array {
        return [
            'short word unchanged' => ['hello', 10, '-', 'hello '],
            'long word split' => ['abcdefghij', 4, '-', 'abcd-efgh-ij '],
            'multiple words' => ['ab cd', 2, '-', 'ab cd '],
        ];
    }

    #[DataProvider('input_provider')]
    public function test_input(string $input, string $expected): void {
        $this->assertSame($expected, Text::input($input));
    }

    public static function input_provider(): array {
        return [
            'trims whitespace' => ['  value  ', 'value'],
            'sanitizes angle brackets' => ['<script>', '_script_'],
            'collapses spaces' => ['a   b', 'a b'],
        ];
    }

    #[DataProvider('is_empty_provider')]
    public function test_is_empty(?string $value, bool $expected): void {
        $this->assertSame($expected, Text::is_empty($value));
    }

    public static function is_empty_provider(): array {
        return [
            'null is empty' => [null, true],
            'blank string is empty' => ['   ', true],
            'non-empty string' => ['x', false],
        ];
    }

    public function test_prefix_and_suffix_helpers(): void {
        $this->assertTrue(Text::is_prefixed_by('prefix-value', 'prefix-'));
        $this->assertFalse(Text::is_prefixed_by('value', 'prefix-'));
        $this->assertTrue(Text::is_suffixed_by('value-suffix', '-suffix'));
        $this->assertFalse(Text::is_suffixed_by('value', '-suffix'));
    }

    public function test_trim_once_helpers(): void {
        $this->assertSame('value', Text::ltrim_once('prefix-value', 'prefix-'));
        $this->assertSame('prefix-value', Text::ltrim_once('prefix-value', 'missing-'));
        $this->assertSame('value', Text::rtrim_once('value-suffix', '-suffix'));
        $this->assertSame('value-suffix', Text::rtrim_once('value-suffix', '-missing'));
    }

    public function test_output_escapes_quotes(): void {
        $this->assertSame('&quot;quoted&quot;', Text::output('"quoted"'));
    }

    public function test_output_applies_custom_translation_map(): void {
        $this->assertSame('a_b', Text::output(' a/b ', ['/' => '_']));
    }

    public function test_prepare_trims_whitespace(): void {
        $this->assertSame('ready', Text::prepare("  ready\t"));
    }

    public function test_sanitize_replaces_unsafe_characters(): void {
        $this->assertSame('_tag_', Text::sanitize('<tag>'));
        $this->assertSame('a b', Text::sanitize('a   b'));
    }

}
