<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use Button;

final class button_test extends html_test_case {

    protected function setUp(): void {
        parent::setUp();

        $reflection = new \ReflectionClass(Button::class);
        $property = $reflection->getProperty('count');
        $property->setAccessible(true);
        $property->setValue(null, 1);
    }

    public function test_submit_button_renders_icon_and_title(): void {
        $button = new Button('Save changes', 'fa fa-save', 'btn-primary');

        $this->assertSame(
            '<button class="btn btn-primary" type="submit">'
            . ' <span class="fa fa-save" aria-hidden="true"></span> Save changes</button>',
            "$button"
        );
    }

    public function test_link_button_renders_anchor(): void {
        $button = new Button('Continue shopping', null, 'btn-link', [], 'shop.php');

        $this->assertSame(
            '<a class="btn btn-link" href="shop.php" id="btn1">Continue shopping</a>',
            "$button"
        );
    }

    public function test_newwindow_sets_blank_target_and_rel(): void {
        $button = new Button('Docs', null, 'btn-secondary', ['newwindow' => true], 'https://example.com/docs');

        $markup = "$button";

        $this->assertStringContainsString('target="_blank"', $markup);
        $this->assertStringContainsString('rel="noreferrer"', $markup);
        $this->assertStringNotContainsString('newwindow', $markup);
    }

    public function test_reset_button_strips_href(): void {
        $notice = null;
        set_error_handler(static function (int $severity, string $message) use (&$notice): bool {
            $notice = $message;

            return true;
        }, E_USER_NOTICE);

        try {
            $button = new Button('Reset form', null, 'btn-secondary', ['type' => 'reset', 'href' => 'back.php']);
            $markup = "$button";
        } finally {
            restore_error_handler();
        }

        $this->assertSame('Cannot use links with reset buttons.', $notice);
        $this->assertStringContainsString('type="reset"', $markup);
        $this->assertStringNotContainsString('href=', $markup);
    }

    public function test_fluent_mutators(): void {
        $button = new Button('Initial');
        $button->set_title('Updated')->set_icon('bi bi-check');

        $this->assertSame('Updated', $button->get_title());
        $this->assertSame('bi bi-check', $button->get_icon());
        $this->assertStringContainsString('bi bi-check', "$button");
    }

    public function test_link_buttons_receive_incremental_ids(): void {
        $first = new Button('One', null, 'btn-link', [], 'one.php');
        $second = new Button('Two', null, 'btn-link', [], 'two.php');

        preg_match('/id="btn(\d+)"/', "$first", $first_match);
        preg_match('/id="btn(\d+)"/', "$second", $second_match);

        $this->assertNotEmpty($first_match[1] ?? null);
        $this->assertSame((int) $first_match[1] + 1, (int) $second_match[1]);
    }
}
