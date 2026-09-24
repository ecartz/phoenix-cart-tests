<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use Tickable;

final class tickable_test extends html_test_case {

    public function test_tick_adds_and_removes_checked_attribute(): void {
        $checkbox = new Tickable('agree', ['value' => 'yes']);

        $checkbox->tick();
        $this->assertStringContainsString('checked="checked"', "$checkbox");

        $checkbox->tick(false);
        $this->assertStringNotContainsString('checked=', "$checkbox");
    }

    public function test_tick_if_requested_honors_on_value(): void {
        $_POST['newsletter'] = 'on';

        $checkbox = new Tickable('newsletter');
        $checkbox->tick_if_requested();

        $this->assertStringContainsString('checked="checked"', "$checkbox");
    }

    public function test_defaults_to_checkbox_input(): void {
        $checkbox = new Tickable('remember');

        $this->assertStringContainsString('type="checkbox"', "$checkbox");
    }

}
