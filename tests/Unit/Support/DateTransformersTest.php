<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use AmPmTransformer;
use DateTime;
use DayOfWeekTransformer;
use DayOfYearTransformer;
use DayTransformer;
use Hour1200Transformer;
use Hour2400Transformer;
use MinuteTransformer;
use MonthTransformer;
use PhoenixCart\Tests\Support\PhoenixTestCase;
use QuarterTransformer;
use SecondTransformer;
use YearTransformer;

final class DateTransformersTest extends PhoenixTestCase
{
    private DateTime $dateTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dateTime = new DateTime('2024-06-15 14:30:45');
    }

    /**
     * @dataProvider yearFormatProvider
     */
    public function testYearTransformer(int $length, string $expected): void
    {
        $transformer = new YearTransformer();

        $this->assertSame($expected, $transformer->format($this->dateTime, $length));
        $this->assertSame(['year' => 2024], $transformer->extractDateOptions('2024', $length));
    }

    public static function yearFormatProvider(): array
    {
        return [
            'two digit year' => [2, '24'],
            'four digit year' => [4, '2024'],
            'padded year' => [6, '002024'],
        ];
    }

    /**
     * @dataProvider monthFormatProvider
     */
    public function testMonthTransformer(int $length, string $expected): void
    {
        $transformer = new MonthTransformer();

        $this->assertSame($expected, $transformer->format($this->dateTime, $length));
    }

    public static function monthFormatProvider(): array
    {
        return [
            'numeric month' => [1, '6'],
            'padded month' => [2, '06'],
            'short month' => [3, 'Jun'],
            'full month' => [4, 'June'],
            'narrow month' => [5, 'J'],
        ];
    }

    public function testMonthTransformerExtractsNumericAndNamedMonths(): void
    {
        $transformer = new MonthTransformer();

        $this->assertSame(['month' => 6], $transformer->extractDateOptions('6', 1));
        $this->assertSame(['month' => 6], $transformer->extractDateOptions('Jun', 3));
        $this->assertSame(['month' => 6], $transformer->extractDateOptions('June', 4));
    }

    /**
     * @dataProvider dayFormatProvider
     */
    public function testDayTransformer(int $length, string $expected): void
    {
        $transformer = new DayTransformer();

        $this->assertSame($expected, $transformer->format($this->dateTime, $length));
        $this->assertSame(['day' => 15], $transformer->extractDateOptions('15', $length));
    }

    public static function dayFormatProvider(): array
    {
        return [
            'single digit day' => [1, '15'],
            'padded day' => [2, '15'],
            'wide day' => [3, '015'],
        ];
    }

    public function testHourTransformers(): void
    {
        $hour2400 = new Hour2400Transformer();
        $hour1200 = new Hour1200Transformer();

        $this->assertSame('14', $hour2400->format($this->dateTime, 2));
        $this->assertSame('02', $hour1200->format($this->dateTime, 2));
        $this->assertSame(0, $hour2400->normalizeHour(3, 'AM'));
        $this->assertSame(12, $hour2400->normalizeHour(3, 'PM'));
        $this->assertSame(15, $hour1200->normalizeHour(3, 'PM'));
    }

    public function testMinuteAndSecondTransformers(): void
    {
        $minute = new MinuteTransformer();
        $second = new SecondTransformer();

        $this->assertSame('30', $minute->format($this->dateTime, 2));
        $this->assertSame('45', $second->format($this->dateTime, 2));
        $this->assertSame(['minute' => 30], $minute->extractDateOptions('30', 2));
        $this->assertSame(['second' => 45], $second->extractDateOptions('45', 2));
    }

    public function testAmPmTransformer(): void
    {
        $transformer = new AmPmTransformer();

        $this->assertSame('PM', $transformer->format($this->dateTime, 2));
        $this->assertSame(['marker' => 'PM'], $transformer->extractDateOptions('PM', 2));
        $this->assertSame('AM|PM', $transformer->getReverseMatchingRegExp(2));
    }

    /**
     * @dataProvider dayOfWeekFormatProvider
     */
    public function testDayOfWeekTransformer(int $length, string $expected): void
    {
        $transformer = new DayOfWeekTransformer();

        $this->assertSame($expected, $transformer->format($this->dateTime, $length));
    }

    public static function dayOfWeekFormatProvider(): array
    {
        return [
            'abbreviated day' => [3, 'Sat'],
            'full day' => [4, 'Saturday'],
            'narrow day' => [5, 'S'],
            'two letter day' => [6, 'Sa'],
        ];
    }

    /**
     * @dataProvider quarterFormatProvider
     */
    public function testQuarterTransformer(int $length, string $expected): void
    {
        $transformer = new QuarterTransformer();

        $this->assertSame($expected, $transformer->format($this->dateTime, $length));
    }

    public static function quarterFormatProvider(): array
    {
        return [
            'single digit quarter' => [1, '2'],
            'padded quarter' => [2, '02'],
            'short quarter' => [3, 'Q2'],
            'full quarter' => [4, '2nd quarter'],
        ];
    }

    public function testDayOfYearTransformer(): void
    {
        $transformer = new DayOfYearTransformer();

        $this->assertSame('167', $transformer->format($this->dateTime, 3));
        $this->assertSame('\d{3}', $transformer->getReverseMatchingRegExp(3));
    }

    public function testReverseMatchingRegExpSamples(): void
    {
        $year = new YearTransformer();
        $month = new MonthTransformer();

        $this->assertSame('\d{2}', $year->getReverseMatchingRegExp(2));
        $this->assertSame('\d{1,4}', $year->getReverseMatchingRegExp(4));
        $this->assertSame('\d{1,2}', $month->getReverseMatchingRegExp(1));
        $this->assertStringContainsString('Jun', $month->getReverseMatchingRegExp(3));
    }
}
