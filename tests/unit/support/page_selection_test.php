<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use page_selection;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\DataProvider;

final class page_selection_test extends phoenix_test_case
{
    /**
     * @param list<string> $expected
     */
    #[DataProvider('pages_provider')]
    public function test_get_pages_splits_semicolon_list(string $input, array $expected): void
    {
        $this->assertSame($expected, page_selection::_get_pages($input));
    }

    public static function pages_provider(): array
    {
        return [
            'empty string' => ['', []],
            'single page' => ['index.php', ['index.php']],
            'multiple pages' => [' index.php ; checkout.php ; ', ['index.php', 'checkout.php']],
        ];
    }

    public function test_show_pages_explodes_semicolon_list_with_line_breaks(): void
    {
        $output = page_selection::_show_pages('index.php;checkout.php');

        $this->assertSame(
            nl2br(implode("\n", ['index.php', 'checkout.php'])),
            $output
        );
    }
}
