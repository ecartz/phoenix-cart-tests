<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use PhoenixCart\Tests\Support\phoenix_test_case;
use Template;
use PHPUnit\Framework\Attributes\DataProvider;

final class template_blocks_test extends phoenix_test_case
{
    private function create_template(): Template
    {
        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        return new Template(new default_template());
    }

    public function test_title_round_trip(): void
    {
        $template = $this->create_template();
        $template->set_title('Checkout');

        $this->assertSame('Checkout', $template->get_title());
    }

    /**
     * @param list<string> $blocks
     */
    #[DataProvider('blocks_provider')]
    public function test_block_helpers(array $blocks, string $group, bool $has_blocks, ?string $expected_output): void
    {
        $template = $this->create_template();

        foreach ($blocks as $block) {
            $template->add_block($block, $group);
        }

        $this->assertSame($has_blocks, $template->has_blocks($group));

        if ($expected_output === null) {
            $this->assertNull($template->get_blocks($group));
        } else {
            $this->assertSame($expected_output, $template->get_blocks($group));
        }
    }

    public static function blocks_provider(): array
    {
        return [
            'empty group has no blocks' => [[], 'footer', false, null],
            'single block returned as string' => [['<nav />'], 'header', true, '<nav />'],
            'multiple blocks joined with newline' => [['<a />', '<b />'], 'header', true, "<a />\n<b />"],
        ];
    }

    public function test_has_blocks_is_false_for_unknown_group(): void
    {
        $template = $this->create_template();

        $this->assertFalse($template->has_blocks('unknown'));
    }

    public function test_get_template_returns_injected_template_object(): void
    {
        $default_template = new default_template();
        $template = new Template($default_template);

        $this->assertSame($default_template, $template->get_template());
    }
}
