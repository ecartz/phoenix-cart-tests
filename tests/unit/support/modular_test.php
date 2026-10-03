<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use modular;
use PhoenixCart\Tests\support\phoenix_test_case;

final class modular_test extends phoenix_test_case {

    public function test_display_layout_renders_slot_letters_with_widths_and_colors(): void {
        $html = modular::display_layout([
            'A' => ['width' => 'col-6'],
            'B' => ['width' => 'col-6'],
        ]);

        $this->assertStringContainsString('container text-center', $html);
        $this->assertStringContainsString('col-6 bg-dark text-white', $html);
        $this->assertStringContainsString('col-6 bg-dark-subtle', $html);
        $this->assertStringContainsString('>A</div>', $html);
        $this->assertStringContainsString('>B</div>', $html);
        $this->assertStringNotContainsString('alert alert-light', $html);
    }

}
