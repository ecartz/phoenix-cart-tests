<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use Input;

final class InputTest extends HtmlTestCase
{
    public function testToStringAppliesDefaults(): void
    {
        $input = new Input('email', ['type' => 'email']);

        $this->assertSame(
            '<input name="email" type="email" class="form-control">',
            (string) $input
        );
    }

    public function testRequireAddsAccessibilityAttributes(): void
    {
        $input = new Input('name');
        $input->require();

        $this->assertStringContainsString(' required', (string) $input);
        $this->assertStringContainsString(' aria-required="true"', (string) $input);

        $input->require(false);

        $this->assertStringNotContainsString(' required', (string) $input);
        $this->assertStringNotContainsString(' aria-required=', (string) $input);
    }

    public function testDefaultValuePrefersRequestValue(): void
    {
        $_POST['city'] = 'London';

        $input = new Input('city');
        $input->default_value('Paris');

        $this->assertStringContainsString('value="London"', (string) $input);
    }

    public function testDefaultValueFallsBackToProvidedDefault(): void
    {
        $input = new Input('city');
        $input->default_value('Paris');

        $this->assertStringContainsString('value="Paris"', (string) $input);
    }

    public function testEmptyValueAttributeIsOmitted(): void
    {
        $input = new Input('notes');
        $input->set('value', '');

        $this->assertStringNotContainsString('value=', (string) $input);
    }

    public function testConstructorAcceptsExplicitTypeArgument(): void
    {
        $input = new Input('agree', [], 'checkbox');

        $this->assertStringContainsString('type="checkbox"', (string) $input);
    }
}
