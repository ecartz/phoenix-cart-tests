<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\html;

use Select;
use PHPUnit\Framework\Attributes\DataProvider;

final class select_test extends html_test_case {

    #[DataProvider('selection_provider')]
    public function test_draw_marks_selected_option(?string $selection, string $expected_selected_value): void {
        $select = new Select('country', [
            ['id' => '1', 'text' => 'UK'],
            ['id' => '2', 'text' => 'US'],
        ]);
        $select->set_selection($selection);

        $markup = "$select";

        $this->assertStringContainsString(
            'value="' . $expected_selected_value . '" selected="selected"',
            $markup
        );
        $this->assertSame(1, substr_count($markup, 'selected="selected"'));
    }

    public static function selection_provider(): array {
        return [
            'explicit selection' => ['2', '2'],
            'first matching option wins' => ['1', '1'],
        ];
    }

    public function test_set_default_selection_only_when_unset(): void {
        $select = new Select('status', [
            ['id' => 'open', 'text' => 'Open'],
            ['id' => 'closed', 'text' => 'Closed'],
        ]);

        $select->set_selection('closed')->set_default_selection('open');

        $this->assertStringContainsString('value="closed" selected="selected"', "$select");
    }

    public function test_request_value_becomes_selection(): void {
        $_GET['currency'] = 'gbp';

        $select = new Select('currency', [
            ['id' => 'usd', 'text' => 'USD'],
            ['id' => 'gbp', 'text' => 'GBP'],
        ]);

        $this->assertSame('gbp', $select->get_selection());
        $this->assertStringContainsString('value="gbp" selected="selected"', "$select");
    }

    public function test_required_appends_marker(): void {
        $select = new Select('required-field', [
            ['id' => 'yes', 'text' => 'Yes'],
        ]);
        $select->set_required(true);

        $this->assertStringEndsWith(TEXT_FIELD_REQUIRED, "$select");
    }

    public function test_option_parameters_are_escaped(): void {
        $select = new Select('sizes', [
            [
                'id' => 'm',
                'text' => 'Medium "quote"',
                'parameters' => ['data-label' => '<tag>'],
            ],
        ]);

        $markup = "$select";

        $this->assertStringContainsString('data-label="&lt;tag&gt;"', $markup);
        $this->assertStringContainsString('>Medium &quot;quote&quot;</option>', $markup);
    }

    public function test_fluent_option_mutators(): void {
        $select = new Select('colors', [['id' => 'red', 'text' => 'Red']]);
        $select->add_option(['id' => 'blue', 'text' => 'Blue'])
            ->set_options([['id' => 'green', 'text' => 'Green']]);

        $this->assertSame([['id' => 'green', 'text' => 'Green']], $select->get_options());
    }

}
