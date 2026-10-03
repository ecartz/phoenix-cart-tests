<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use PhoenixCart\Tests\support\phoenix_test_case;
use Search;
use PHPUnit\Framework\Attributes\DataProvider;

final class search_test extends phoenix_test_case
{
    protected function setUp(): void {
        parent::setUp();

        if (!defined('ADVANCED_SEARCH_DEFAULT_OPERATOR')) {
            define('ADVANCED_SEARCH_DEFAULT_OPERATOR', 'and');
        }
    }

    /**
     * @param list<string>|null $expected
     */
    #[DataProvider('build_provider')]
    public function test_build(string $input, ?array $expected): void {
        $this->assertSame($expected, Search::build($input));
    }

    public static function build_provider(): array {
        return [
            'two terms gain default and operator' => [
                'alpha beta',
                ['alpha', 'and', 'beta'],
            ],
            'quoted phrase stays single token' => [
                '"exact phrase" extra',
                ['exact phrase', 'and', 'extra'],
            ],
            'explicit operators preserved' => [
                'alpha or beta',
                ['alpha', 'or', 'beta'],
            ],
            'parenthesized groups stay balanced' => [
                '(alpha beta) or gamma',
                ['(', 'alpha', 'and', 'beta', ')', 'or', 'gamma'],
            ],
            'unbalanced expression returns null' => [
                'alpha and or beta',
                null,
            ],
            'unbalanced parentheses return null' => [
                '(alpha beta',
                null,
            ],
        ];
    }

    /**
     * @param list<string> $tokens
     */
    #[DataProvider('balanced_provider')]
    public function test_is_balanced(array $tokens, bool $expected): void {
        $this->assertSame($expected, Search::is_balanced($tokens));
    }

    public static function balanced_provider(): array {
        return [
            'valid search tokens' => [['term', 'and', 'other'], true],
            'balanced parentheses' => [['(', 'alpha', 'and', 'beta', ')'], true],
            'extra logical operator' => [['term', 'and', 'or', 'other'], false],
            'unclosed parenthesis' => [['(', 'term'], false],
        ];
    }
}
