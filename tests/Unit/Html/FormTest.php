<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use Form;
use Session;

final class FormTest extends HtmlTestCase
{
    public function testDrawRendersOpeningTagAndHiddenFields(): void
    {
        $form = new Form('checkout', 'checkout.php', 'post', ['id' => 'checkout-form'], false);
        $form->hide('action', 'process');

        $this->assertSame(
            '<form name="checkout" action="checkout.php" method="post" id="checkout-form">'
            . '<input name="action" type="hidden" value="process" class="form-control">',
            $form->draw()
        );
    }

    public function testCloseReturnsClosingTag(): void
    {
        $this->assertSame('</form>', (new Form('test', 'action.php'))->close());
    }

    public function testHideSessionIdAddsSessionHiddenInput(): void
    {
        $reflection = new \ReflectionClass(Session::class);
        $started = $reflection->getProperty('started');
        $started->setAccessible(true);
        $started->setValue(null, true);

        $GLOBALS['SID'] = session_name() . '=' . session_id();

        $form = new Form('session', 'index.php', 'post', [], false);
        $form->hide_session_id();

        $markup = $form->draw();

        $this->assertStringContainsString('type="hidden"', $markup);
        $this->assertStringContainsString('name="' . session_name() . '"', $markup);
        $this->assertStringContainsString('value="' . session_id() . '"', $markup);

        unset($GLOBALS['SID']);
        $started->setValue(null, false);
    }

    public function testValidateActionIsSucceedsWithMatchingToken(): void
    {
        $_SESSION['sessiontoken'] = bin2hex(random_bytes(16));
        $_POST['action'] = 'process';
        $_POST['formid'] = $_SESSION['sessiontoken'];

        $this->assertTrue(Form::validate_action_is('process'));
        $this->assertTrue(Form::validate_action_is(['save', 'process']));
    }

    public function testValidateActionIsFailsWithMismatchedToken(): void
    {
        $_SESSION['sessiontoken'] = bin2hex(random_bytes(16));
        $_POST['action'] = 'process';
        $_POST['formid'] = 'invalid-token';

        $this->assertFalse(Form::validate_action_is('process'));
    }

    public function testTokenizeAddsSessionTokenWhenPresent(): void
    {
        $_SESSION['sessiontoken'] = 'token-value';

        $form = new Form('secure', 'secure.php');

        $this->assertStringContainsString(
            '<input name="formid" type="hidden" value="token-value" class="form-control">',
            (string) $form
        );
    }

    public function testBlockProcessingSetsGlobalErrorFlag(): void
    {
        unset($GLOBALS['error']);

        Form::block_processing();

        $this->assertFalse(Form::is_valid());
        $this->assertTrue($GLOBALS['error']);

        unset($GLOBALS['error']);
    }
}
