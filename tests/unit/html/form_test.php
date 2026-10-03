<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\html;

use Form;
use Session;

final class form_test extends html_test_case {

    public function test_draw_renders_opening_tag_and_hidden_fields(): void {
        $form = new Form('checkout', 'checkout.php', 'post', ['id' => 'checkout-form'], false);
        $form->hide('action', 'process');

        $this->assertSame(
            '<form name="checkout" action="checkout.php" method="post" id="checkout-form">'
            . '<input name="action" type="hidden" value="process" class="form-control">',
            $form->draw()
        );
    }

    public function test_close_returns_closing_tag(): void {
        $this->assertSame('</form>', (new Form('test', 'action.php'))->close());
    }

    public function test_hide_session_id_adds_session_hidden_input(): void {
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

    public function test_validate_action_is_succeeds_with_matching_token(): void {
        $_SESSION['sessiontoken'] = bin2hex(random_bytes(16));
        $_POST['action'] = 'process';
        $_POST['formid'] = $_SESSION['sessiontoken'];

        $this->assertTrue(Form::validate_action_is('process'));
        $this->assertTrue(Form::validate_action_is(['save', 'process']));
    }

    public function test_validate_action_is_fails_with_mismatched_token(): void {
        $_SESSION['sessiontoken'] = bin2hex(random_bytes(16));
        $_POST['action'] = 'process';
        $_POST['formid'] = 'invalid-token';

        $this->assertFalse(Form::validate_action_is('process'));
    }

    public function test_tokenize_adds_session_token_when_present(): void {
        $_SESSION['sessiontoken'] = 'token-value';

        $form = new Form('secure', 'secure.php');

        $this->assertStringContainsString(
            '<input name="formid" type="hidden" value="token-value" class="form-control">',
            "$form"
        );
    }

    public function test_block_processing_sets_global_error_flag(): void {
        unset($GLOBALS['error']);

        Form::block_processing();

        $this->assertFalse(Form::is_valid());
        $this->assertTrue($GLOBALS['error']);

        unset($GLOBALS['error']);
    }

}
