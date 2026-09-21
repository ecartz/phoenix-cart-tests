<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use Select;

final class SelectTest extends HtmlTestCase
{
    /**
     * @dataProvider selectionProvider
     */
    public function testDrawMarksSelectedOption(?string $selection, string $expectedSelectedValue): void
    {
        $select = new Select('country', [
            ['id' => '1', 'text' => 'UK'],
            ['id' => '2', 'text' => 'US'],
        ]);
        $select->set_selection($selection);

        $markup = (string) $select;

        $this->assertStringContainsString(
            'value="' . $expectedSelectedValue . '" selected="selected"',
            $markup
        );
        $this->assertSame(1, substr_count($markup, 'selected="selected"'));
    }

    public static function selectionProvider(): array
    {
        return [
            'explicit selection' => ['2', '2'],
            'first matching option wins' => ['1', '1'],
        ];
    }

    public function testSetDefaultSelectionOnlyWhenUnset(): void
    {
        $select = new Select('status', [
            ['id' => 'open', 'text' => 'Open'],
            ['id' => 'closed', 'text' => 'Closed'],
        ]);

        $select->set_selection('closed')->set_default_selection('open');

        $this->assertStringContainsString('value="closed" selected="selected"', (string) $select);
    }

    public function testRequestValueBecomesSelection(): void
    {
        $_GET['currency'] = 'gbp';

        $select = new Select('currency', [
            ['id' => 'usd', 'text' => 'USD'],
            ['id' => 'gbp', 'text' => 'GBP'],
        ]);

        $this->assertSame('gbp', $select->get_selection());
        $this->assertStringContainsString('value="gbp" selected="selected"', (string) $select);
    }

    public function testRequiredAppendsMarker(): void
    {
        $select = new Select('required-field', [
            ['id' => 'yes', 'text' => 'Yes'],
        ]);
        $select->set_required(true);

        $this->assertStringEndsWith(TEXT_FIELD_REQUIRED, (string) $select);
    }

    public function testOptionParametersAreEscaped(): void
    {
        $select = new Select('sizes', [
            [
                'id' => 'm',
                'text' => 'Medium "quote"',
                'parameters' => ['data-label' => '<tag>'],
            ],
        ]);

        $markup = (string) $select;

        $this->assertStringContainsString('data-label="&lt;tag&gt;"', $markup);
        $this->assertStringContainsString('>Medium &quot;quote&quot;</option>', $markup);
    }

    public function testFluentOptionMutators(): void
    {
        $select = new Select('colors', [['id' => 'red', 'text' => 'Red']]);
        $select->add_option(['id' => 'blue', 'text' => 'Blue'])
            ->set_options([['id' => 'green', 'text' => 'Green']]);

        $this->assertSame([['id' => 'green', 'text' => 'Green']], $select->get_options());
    }
}
