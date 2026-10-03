<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use Date;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\DataProvider;

final class date_test extends phoenix_test_case
{
    #[DataProvider('constructor_provider')]
    public function test_constructor(mixed $input, bool $valid): void {
        $date = new Date($input);

        if ($valid) {
            $this->assertNotFalse($date->format('yyyy'));
        } else {
            $this->assertFalse($date->format('yyyy'));
        }
    }

    public static function constructor_provider(): array {
        return [
            'integer timestamp' => [1704067200, true],
            'mysql datetime string' => ['2024-01-01 00:00:00', true],
            'zero date' => ['0000-00-00 00:00:00', false],
            'empty string' => ['', false],
        ];
    }

    public function test_format_returns_localized_value(): void {
        $date = new Date('2024-06-15 10:30:00');

        $this->assertSame('2024', $date->format('yyyy'));
        $this->assertSame('06', $date->format('MM'));
        $this->assertSame('15', $date->format('dd'));
    }

    public function test_format_returns_false_for_invalid_date(): void {
        $date = new Date('0000-00-00 00:00:00');

        $this->assertFalse($date->format('yyyy'));
    }

    public function test_get_timestamp_matches_constructor_input(): void {
        $timestamp = 1704067200;

        $this->assertSame($timestamp, (new Date($timestamp))->get_timestamp());
    }

    public function test_now_returns_current_timestamp(): void {
        $before = time();
        $now = Date::now();
        $after = time();

        $timestamp = $now->get_timestamp();
        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }

    public function test_expound_returns_false_for_invalid_date(): void {
        $this->assertFalse(Date::expound('0000-00-00 00:00:00'));
        $this->assertFalse(Date::expound(''));
    }

    public function test_abridge_returns_false_for_invalid_date(): void {
        $this->assertFalse(Date::abridge('0000-00-00 00:00:00'));
        $this->assertFalse(Date::abridge(''));
    }

    public function test_expound_uses_long_date_formatter_stub(): void {
        $GLOBALS['long_date_formatter'] = new class {
            public function format(int $timestamp): string {
                return 'LONG:' . date('Y-m-d', $timestamp);
            }
        };

        $this->assertSame('LONG:2024-06-15', Date::expound('2024-06-15 10:30:00'));
    }

    public function test_abridge_uses_short_date_formatter_stub(): void {
        $GLOBALS['short_date_formatter'] = new class {
            public function format(int $timestamp): string {
                return 'SHORT:' . date('Y-m', $timestamp);
            }
        };

        $this->assertSame('SHORT:2024-06', Date::abridge('2024-06-15 10:30:00'));
    }
}
