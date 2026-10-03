<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\template;

use default_template;
use hooks;
use PhoenixCart\Tests\support\phoenix_test_case;
use Template;
use PHPUnit\Framework\Attributes\DataProvider;

final class template_content_test extends phoenix_test_case
{
    protected function setUp(): void {
        parent::setUp();

        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }

        if (!defined('MODULE_CONTENT_INSTALLED')) {
            define('MODULE_CONTENT_INSTALLED', '');
        }

        $GLOBALS['all_hooks'] = new hooks('shop');
    }

    private function create_template(): Template {
        return new Template(new default_template());
    }

    /**
     * @param list<string> $chunks
     */
    #[DataProvider('content_provider')]
    public function test_content_helpers(array $chunks, string $group, bool $has_content, ?string $expected_output): void {
        $template = $this->create_template();

        foreach ($chunks as $chunk) {
            $template->add_content($chunk, $group);
        }

        $this->assertSame($has_content, $template->has_content($group));

        if ($expected_output === null) {
            $this->assertNull($template->get_content($group));
        } else {
            $this->assertSame($expected_output, $template->get_content($group));
        }
    }

    public static function content_provider(): array {
        return [
            'empty group has no content' => [[], 'unused_group', false, null],
            'single chunk returned as string' => [['<p>hello</p>'], 'body', true, '<p>hello</p>'],
            'multiple chunks joined with newline' => [['<a />', '<b />'], 'body', true, "<a />\n<b />"],
        ];
    }

    public function test_has_content_is_false_for_unknown_group(): void {
        $template = $this->create_template();

        $this->assertFalse($template->has_content('unknown'));
    }
}
