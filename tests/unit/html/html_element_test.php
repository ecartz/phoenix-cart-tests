<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\html;

use html_element;
use PHPUnit\Framework\Attributes\DataProvider;

final class html_element_test extends html_test_case {

    public function test_set_get_and_delete(): void {
        $element = new html_element();

        $element->set('id', 'widget')->set('data-role', 'panel');

        $this->assertSame('widget', $element->get('id'));
        $this->assertSame('panel', $element->get('data-role'));

        $element->delete('data-role');

        $this->assertSame(' id="widget"', $element->stringify_parameters());
    }

    #[DataProvider('append_css_provider')]
    public function test_append_css(string $initial_class, string $append, string $expected): void {
        $parameters = $initial_class === '' ? [] : ['class' => $initial_class];
        $element = new html_element($parameters);

        $element->append_css($append);

        $this->assertSame($expected, $element->get('class'));
    }

    public static function append_css_provider(): array {
        return [
            'creates class attribute' => ['', 'btn', 'btn'],
            'appends to existing classes' => ['foo', 'bar', 'foo bar'],
        ];
    }

    #[DataProvider('stringify_provider')]
    public function test_stringify_parameters(array $parameters, string $expected): void {
        $element = new html_element($parameters);

        $this->assertSame($expected, $element->stringify_parameters());
    }

    public static function stringify_provider(): array {
        return [
            'escapes attribute values' => [
                ['title' => '"quoted"'],
                ' title="&quot;quoted&quot;"',
            ],
            'boolean attribute without value' => [
                ['disabled' => null],
                ' disabled',
            ],
            'multiple attributes preserve order' => [
                ['id' => 'a', 'class' => 'b'],
                ' id="a" class="b"',
            ],
        ];
    }

}
