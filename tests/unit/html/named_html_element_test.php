<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\html;

use named_html_element;

final class named_html_element_test extends html_test_case {

    public function test_constructor_sets_name_attribute(): void {
        $element = new named_html_element('custom_field', ['id' => 'field1', 'class' => 'form-control']);

        $this->assertSame('custom_field', $element->get('name'));
        $this->assertStringContainsString('name="custom_field"', $element->stringify_parameters());
        $this->assertStringContainsString('id="field1"', $element->stringify_parameters());
    }

}
