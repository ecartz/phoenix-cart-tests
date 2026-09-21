<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use html_element;

final class HtmlElementTest extends HtmlTestCase
{
    public function testSetGetAndDelete(): void
    {
        $element = new html_element();

        $element->set('id', 'widget')->set('data-role', 'panel');

        $this->assertSame('widget', $element->get('id'));
        $this->assertSame('panel', $element->get('data-role'));

        $element->delete('data-role');

        $this->assertSame(' id="widget"', $element->stringify_parameters());
    }

    /**
     * @dataProvider appendCssProvider
     */
    public function testAppendCss(string $initialClass, string $append, string $expected): void
    {
        $parameters = $initialClass === '' ? [] : ['class' => $initialClass];
        $element = new html_element($parameters);

        $element->append_css($append);

        $this->assertSame($expected, $element->get('class'));
    }

    public static function appendCssProvider(): array
    {
        return [
            'creates class attribute' => ['', 'btn', 'btn'],
            'appends to existing classes' => ['foo', 'bar', 'foo bar'],
        ];
    }

    /**
     * @dataProvider stringifyProvider
     */
    public function testStringifyParameters(array $parameters, string $expected): void
    {
        $element = new html_element($parameters);

        $this->assertSame($expected, $element->stringify_parameters());
    }

    public static function stringifyProvider(): array
    {
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
