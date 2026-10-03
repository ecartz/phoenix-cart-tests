<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use messageStack;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\DataProvider;

final class message_stack_test extends phoenix_test_case
{
    protected function setUp(): void {
        parent::setUp();

        if (!defined('IMAGE_BUTTON_CLOSE')) {
            define('IMAGE_BUTTON_CLOSE', 'Close');
        }
    }

    public function test_add_and_size_filter_by_class(): void {
        $stack = new messageStack();
        $stack->add('general', 'First error', 'error');
        $stack->add('checkout', 'Checkout warning', 'warning');
        $stack->add('general', 'Second error', 'error');

        $this->assertSame(2, $stack->size('general'));
        $this->assertSame(1, $stack->size('checkout'));
    }

    #[DataProvider('alert_type_provider')]
    public function test_output_includes_alert_class_for_type(string $type, string $expected_class): void {
        $stack = new messageStack();
        $stack->add('banner', 'Notice text', $type);

        $output = $stack->output('banner');

        $this->assertStringContainsString($expected_class, $output);
        $this->assertStringContainsString('Notice text', $output);
        $this->assertStringContainsString('aria-label="Close"', $output);
    }

    public static function alert_type_provider(): array {
        return [
            'error' => ['error', 'alert-danger'],
            'warning' => ['warning', 'alert-warning'],
            'success' => ['success', 'alert-success'],
            'info default' => ['info', 'alert-info'],
        ];
    }

    public function test_reset_clears_messages(): void {
        $stack = new messageStack();
        $stack->add('general', 'Temporary', 'error');
        $stack->reset();

        $this->assertSame(0, $stack->size('general'));
    }

    public function test_add_classed_delegates_to_add(): void {
        $stack = new messageStack();
        $stack->add_classed('account', 'Saved', 'success');

        $this->assertSame(1, $stack->size('account'));
        $this->assertStringContainsString('alert-success', $stack->output('account'));
    }

    public function test_add_session_stores_messages_for_next_request(): void {
        unset($_SESSION['messageToStack']);

        $stack = new messageStack();
        $stack->add_session('checkout', 'Please review shipping', 'warning');

        $this->assertSame(
            [
                [
                    'class' => 'checkout',
                    'text' => 'Please review shipping',
                    'type' => 'warning',
                ],
            ],
            $_SESSION['messageToStack']
        );
        $this->assertSame(0, $stack->size('checkout'));
    }

    public function test_constructor_restores_and_clears_session_messages(): void {
        $_SESSION['messageToStack'] = [
            [
                'class' => 'general',
                'text' => 'Welcome back',
                'type' => 'success',
            ],
        ];

        $stack = new messageStack();

        $this->assertArrayNotHasKey('messageToStack', $_SESSION);
        $this->assertSame(1, $stack->size('general'));
        $this->assertStringContainsString('Welcome back', $stack->output('general'));
    }
}
