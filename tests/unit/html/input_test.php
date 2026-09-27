<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\html;

use Input;

final class input_test extends html_test_case {

    public function test_to_string_applies_defaults(): void {
        $input = new Input('email', ['type' => 'email']);

        $this->assertSame(
            '<input name="email" type="email" class="form-control">',
            "$input"
        );
    }

    public function test_require_adds_accessibility_attributes(): void {
        $input = new Input('name');
        $input->require();

        $this->assertStringContainsString(' required', "$input");
        $this->assertStringContainsString(' aria-required="true"', "$input");

        $input->require(false);

        $this->assertStringNotContainsString(' required', "$input");
        $this->assertStringNotContainsString(' aria-required=', "$input");
    }

    public function test_default_value_prefers_request_value(): void {
        $_POST['city'] = 'London';

        $input = new Input('city');
        $input->default_value('Paris');

        $this->assertStringContainsString('value="London"', "$input");
    }

    public function test_default_value_falls_back_to_provided_default(): void {
        $input = new Input('city');
        $input->default_value('Paris');

        $this->assertStringContainsString('value="Paris"', "$input");
    }

    public function test_empty_value_attribute_is_omitted(): void {
        $input = new Input('notes');
        $input->set('value', '');

        $this->assertStringNotContainsString('value=', "$input");
    }

    public function test_constructor_accepts_explicit_type_argument(): void {
        $input = new Input('agree', [], 'checkbox');

        $this->assertStringContainsString('type="checkbox"', "$input");
    }
}
