<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use PhoenixCart\Tests\Support\PhoenixTestCase;
use Template;

final class TemplateBlocksTest extends PhoenixTestCase
{
    private function createTemplate(): Template
    {
        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        return new Template(new default_template());
    }

    public function testTitleRoundTrip(): void
    {
        $template = $this->createTemplate();
        $template->set_title('Checkout');

        $this->assertSame('Checkout', $template->get_title());
    }

    /**
     * @dataProvider blocksProvider
     *
     * @param list<string> $blocks
     */
    public function testBlockHelpers(array $blocks, string $group, bool $hasBlocks, ?string $expectedOutput): void
    {
        $template = $this->createTemplate();

        foreach ($blocks as $block) {
            $template->add_block($block, $group);
        }

        $this->assertSame($hasBlocks, $template->has_blocks($group));

        if ($expectedOutput === null) {
            $this->assertNull($template->get_blocks($group));
        } else {
            $this->assertSame($expectedOutput, $template->get_blocks($group));
        }
    }

    public static function blocksProvider(): array
    {
        return [
            'empty group has no blocks' => [[], 'footer', false, null],
            'single block returned as string' => [['<nav />'], 'header', true, '<nav />'],
            'multiple blocks joined with newline' => [['<a />', '<b />'], 'header', true, "<a />\n<b />"],
        ];
    }

    public function testHasBlocksIsFalseForUnknownGroup(): void
    {
        $template = $this->createTemplate();

        $this->assertFalse($template->has_blocks('unknown'));
    }

    public function testGetTemplateReturnsInjectedTemplateObject(): void
    {
        $defaultTemplate = new default_template();
        $template = new Template($defaultTemplate);

        $this->assertSame($defaultTemplate, $template->get_template());
    }
}
