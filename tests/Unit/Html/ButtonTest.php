<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use Button;

final class ButtonTest extends HtmlTestCase
{
    public function testSubmitButtonRendersIconAndTitle(): void
    {
        $button = new Button('Save changes', 'fa fa-save', 'btn-primary');

        $this->assertSame(
            '<button class="btn btn-primary" type="submit">'
            . ' <span class="fa fa-save" aria-hidden="true"></span> Save changes</button>',
            (string) $button
        );
    }

    public function testLinkButtonRendersAnchor(): void
    {
        $button = new Button('Continue shopping', null, 'btn-link', [], 'shop.php');

        $this->assertSame(
            '<a class="btn btn-link" href="shop.php" id="btn1">Continue shopping</a>',
            (string) $button
        );
    }

    public function testNewwindowSetsBlankTargetAndRel(): void
    {
        $button = new Button('Docs', null, 'btn-secondary', ['newwindow' => true], 'https://example.com/docs');

        $markup = (string) $button;

        $this->assertStringContainsString('target="_blank"', $markup);
        $this->assertStringContainsString('rel="noreferrer"', $markup);
        $this->assertStringNotContainsString('newwindow', $markup);
    }

    public function testResetButtonStripsHref(): void
    {
        $notice = null;
        set_error_handler(static function (int $severity, string $message) use (&$notice): bool {
            $notice = $message;

            return true;
        }, E_USER_NOTICE);

        $button = new Button('Reset form', null, 'btn-secondary', ['type' => 'reset', 'href' => 'back.php']);
        $markup = (string) $button;

        restore_error_handler();

        $this->assertSame('Cannot use links with reset buttons.', $notice);
        $this->assertStringContainsString('type="reset"', $markup);
        $this->assertStringNotContainsString('href=', $markup);
    }

    public function testFluentMutators(): void
    {
        $button = new Button('Initial');
        $button->set_title('Updated')->set_icon('bi bi-check');

        $this->assertSame('Updated', $button->get_title());
        $this->assertSame('bi bi-check', $button->get_icon());
        $this->assertStringContainsString('bi bi-check', (string) $button);
    }

    public function testLinkButtonsReceiveIncrementalIds(): void
    {
        $first = new Button('One', null, 'btn-link', [], 'one.php');
        $second = new Button('Two', null, 'btn-link', [], 'two.php');

        preg_match('/id="btn(\d+)"/', (string) $first, $firstMatch);
        preg_match('/id="btn(\d+)"/', (string) $second, $secondMatch);

        $this->assertNotEmpty($firstMatch[1] ?? null);
        $this->assertSame((int) $firstMatch[1] + 1, (int) $secondMatch[1]);
    }
}
