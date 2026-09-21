<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use hooks;
use PhoenixCart\Tests\Support\PhoenixTestCase;
use Template;

final class TemplateContentTest extends PhoenixTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        if (!defined('MODULE_CONTENT_INSTALLED')) {
            define('MODULE_CONTENT_INSTALLED', '');
        }

        $GLOBALS['all_hooks'] = new hooks('shop');
    }

    private function createTemplate(): Template
    {
        return new Template(new default_template());
    }

    /**
     * @dataProvider contentProvider
     *
     * @param list<string> $chunks
     */
    public function testContentHelpers(array $chunks, string $group, bool $hasContent, ?string $expectedOutput): void
    {
        $template = $this->createTemplate();

        foreach ($chunks as $chunk) {
            $template->add_content($chunk, $group);
        }

        $this->assertSame($hasContent, $template->has_content($group));

        if ($expectedOutput === null) {
            $this->assertNull($template->get_content($group));
        } else {
            $this->assertSame($expectedOutput, $template->get_content($group));
        }
    }

    public static function contentProvider(): array
    {
        return [
            'empty group has no content' => [[], 'unused_group', false, null],
            'single chunk returned as string' => [['<p>hello</p>'], 'body', true, '<p>hello</p>'],
            'multiple chunks joined with newline' => [['<a />', '<b />'], 'body', true, "<a />\n<b />"],
        ];
    }

    public function testHasContentIsFalseForUnknownGroup(): void
    {
        $template = $this->createTemplate();

        $this->assertFalse($template->has_content('unknown'));
    }
}
