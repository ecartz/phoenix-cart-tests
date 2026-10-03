<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use currencies;
use PhoenixCart\Tests\support\mock_catalog_database;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[Group('mockdb')]
final class currencies_test extends phoenix_test_case {

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_constructor_loads_currencies_and_formats(): void {
        if (!defined('DEFAULT_CURRENCY')) {
            define('DEFAULT_CURRENCY', 'USD');
        }

        (new mock_catalog_database([], [
            'currencies' => [
                [
                    'code' => 'USD',
                    'title' => 'US Dollar',
                    'symbol_left' => '$',
                    'symbol_right' => '',
                    'decimal_point' => '.',
                    'thousands_point' => ',',
                    'decimal_places' => '2',
                    'value' => '1.00000000',
                ],
                [
                    'code' => 'EUR',
                    'title' => 'Euro',
                    'symbol_left' => '',
                    'symbol_right' => ' EUR',
                    'decimal_point' => ',',
                    'thousands_point' => '.',
                    'decimal_places' => '2',
                    'value' => '0.85000000',
                ],
            ],
        ]))->install_as_global();

        $currencies = new currencies();

        $this->assertTrue($currencies->is_set('USD'));
        $this->assertTrue($currencies->is_set('EUR'));
        $this->assertFalse($currencies->is_set('GBP'));
        $this->assertSame('1.00000000', $currencies->get_value('USD'));
        $this->assertSame('$10.00', $currencies->format(10, false, 'USD'));
        $this->assertSame('8,50 EUR', $currencies->format(10, true, 'EUR'));
    }

}
