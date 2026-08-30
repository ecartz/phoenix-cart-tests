<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use Date;
use PhoenixCart\Tests\Support\PhoenixTestCase;

final class DateTest extends PhoenixTestCase
{
    /**
     * @dataProvider constructorProvider
     */
    public function testConstructor(mixed $input, bool $valid): void
    {
        $date = new Date($input);

        if ($valid) {
            $this->assertNotFalse($date->format('yyyy'));
        } else {
            $this->assertFalse($date->format('yyyy'));
        }
    }

    public static function constructorProvider(): array
    {
        return [
            'integer timestamp' => [1704067200, true],
            'mysql datetime string' => ['2024-01-01 00:00:00', true],
            'zero date' => ['0000-00-00 00:00:00', false],
            'empty string' => ['', false],
        ];
    }

    public function testFormatReturnsLocalizedValue(): void
    {
        $date = new Date('2024-06-15 10:30:00');

        $this->assertSame('2024', $date->format('yyyy'));
        $this->assertSame('06', $date->format('MM'));
        $this->assertSame('15', $date->format('dd'));
    }

    public function testFormatReturnsFalseForInvalidDate(): void
    {
        $date = new Date('0000-00-00 00:00:00');

        $this->assertFalse($date->format('yyyy'));
    }

    public function testGetTimestampMatchesConstructorInput(): void
    {
        $timestamp = 1704067200;

        $this->assertSame($timestamp, (new Date($timestamp))->get_timestamp());
    }

    public function testNowReturnsCurrentTimestamp(): void
    {
        $before = time();
        $now = Date::now();
        $after = time();

        $timestamp = $now->get_timestamp();
        $this->assertGreaterThanOrEqual($before, $timestamp);
        $this->assertLessThanOrEqual($after, $timestamp);
    }

    public function testExpoundRequiresGlobalFormatter(): void
    {
        $this->markTestSkipped('Date::expound() requires $GLOBALS[\'long_date_formatter\'] from application bootstrap.');
    }

    public function testAbridgeRequiresGlobalFormatter(): void
    {
        $this->markTestSkipped('Date::abridge() requires $GLOBALS[\'short_date_formatter\'] from application bootstrap.');
    }
}
