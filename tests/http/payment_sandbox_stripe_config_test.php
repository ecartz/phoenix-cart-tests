<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PhoenixCart\Tests\support\payment_sandbox_bootstrap;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
#[Group('payment_sandbox')]
final class payment_sandbox_stripe_config_test extends http_test_case
{
    public static function setUpBeforeClass(): void {
        if (!payment_sandbox_bootstrap::is_enabled()) {
            self::markTestSkipped(
                'Payment sandbox skipped. Set PHOENIX_PAYMENT_SANDBOX_ENABLED=1 and Stripe test key env vars.'
            );
        }

        payment_sandbox_bootstrap::apply_to_database();

        parent::setUpBeforeClass();
    }

    public function test_stripe_sca_test_keys_are_stored_in_configuration(): void {
        $publishable = payment_sandbox_bootstrap::fetch_configuration_value(
            'MODULE_PAYMENT_STRIPE_SCA_TEST_PUBLISHABLE_KEY'
        );
        $secret = payment_sandbox_bootstrap::fetch_configuration_value(
            'MODULE_PAYMENT_STRIPE_SCA_TEST_SECRET_KEY'
        );

        $this->assertNotNull($publishable);
        $this->assertStringStartsWith('pk_test_', $publishable);

        $this->assertNotNull($secret);
        $this->assertGreaterThan(10, strlen($secret));
    }

    public function test_homepage_html_does_not_contain_stripe_secret(): void {
        $response = $this->get_http()->request('GET', '/');

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $secret = getenv('PHOENIX_STRIPE_SCA_TEST_SECRET_KEY');
        $this->assertIsString($secret);
        $this->assertNotSame('', $secret);
        $this->assertStringNotContainsString($secret, $body);
    }
}
