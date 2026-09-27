<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use AmPmTransformer;
use DateTime;
use DateTimeZone;
use DayOfWeekTransformer;
use DayOfYearTransformer;
use DayTransformer;
use FullTransformer;
use Hour1200Transformer;
use Hour1201Transformer;
use Hour2400Transformer;
use Hour2401Transformer;
use MinuteTransformer;
use MonthTransformer;
use PhoenixCart\Tests\support\phoenix_test_case;
use QuarterTransformer;
use SecondTransformer;
use TimezoneTransformer;
use YearTransformer;
use PHPUnit\Framework\Attributes\DataProvider;

final class date_transformers_test extends phoenix_test_case
{
    private DateTime $date_time;

    protected function setUp(): void
    {
        parent::setUp();

        $this->date_time = new DateTime('2024-06-15 14:30:45');
    }

    #[DataProvider('year_format_provider')]
    public function test_year_transformer(int $length, string $expected): void
    {
        $transformer = new YearTransformer();

        $this->assertSame($expected, $transformer->format($this->date_time, $length));
        $this->assertSame(['year' => 2024], $transformer->extractDateOptions('2024', $length));
    }

    public static function year_format_provider(): array
    {
        return [
            'two digit year' => [2, '24'],
            'four digit year' => [4, '2024'],
            'padded year' => [6, '002024'],
        ];
    }

    #[DataProvider('month_format_provider')]
    public function test_month_transformer(int $length, string $expected): void
    {
        $transformer = new MonthTransformer();

        $this->assertSame($expected, $transformer->format($this->date_time, $length));
    }

    public static function month_format_provider(): array
    {
        return [
            'numeric month' => [1, '6'],
            'padded month' => [2, '06'],
            'short month' => [3, 'Jun'],
            'full month' => [4, 'June'],
            'narrow month' => [5, 'J'],
        ];
    }

    public function test_month_transformer_extracts_numeric_and_named_months(): void
    {
        $transformer = new MonthTransformer();

        $this->assertSame(['month' => 6], $transformer->extractDateOptions('6', 1));
        $this->assertSame(['month' => 6], $transformer->extractDateOptions('Jun', 3));
        $this->assertSame(['month' => 6], $transformer->extractDateOptions('June', 4));
    }

    #[DataProvider('day_format_provider')]
    public function test_day_transformer(int $length, string $expected): void
    {
        $transformer = new DayTransformer();

        $this->assertSame($expected, $transformer->format($this->date_time, $length));
        $this->assertSame(['day' => 15], $transformer->extractDateOptions('15', $length));
    }

    public static function day_format_provider(): array
    {
        return [
            'single digit day' => [1, '15'],
            'padded day' => [2, '15'],
            'wide day' => [3, '015'],
        ];
    }

    public function test_hour_transformers(): void
    {
        $hour2400 = new Hour2400Transformer();
        $hour1200 = new Hour1200Transformer();

        $this->assertSame('14', $hour2400->format($this->date_time, 2));
        $this->assertSame('02', $hour1200->format($this->date_time, 2));
        $this->assertSame(0, $hour2400->normalizeHour(3, 'AM'));
        $this->assertSame(12, $hour2400->normalizeHour(3, 'PM'));
        $this->assertSame(15, $hour1200->normalizeHour(3, 'PM'));
    }

    public function test_minute_and_second_transformers(): void
    {
        $minute = new MinuteTransformer();
        $second = new SecondTransformer();

        $this->assertSame('30', $minute->format($this->date_time, 2));
        $this->assertSame('45', $second->format($this->date_time, 2));
        $this->assertSame(['minute' => 30], $minute->extractDateOptions('30', 2));
        $this->assertSame(['second' => 45], $second->extractDateOptions('45', 2));
    }

    public function test_am_pm_transformer(): void
    {
        $transformer = new AmPmTransformer();

        $this->assertSame('PM', $transformer->format($this->date_time, 2));
        $this->assertSame(['marker' => 'PM'], $transformer->extractDateOptions('PM', 2));
        $this->assertSame('AM|PM', $transformer->getReverseMatchingRegExp(2));
    }

    #[DataProvider('day_of_week_format_provider')]
    public function test_day_of_week_transformer(int $length, string $expected): void
    {
        $transformer = new DayOfWeekTransformer();

        $this->assertSame($expected, $transformer->format($this->date_time, $length));
    }

    public static function day_of_week_format_provider(): array
    {
        return [
            'abbreviated day' => [3, 'Sat'],
            'full day' => [4, 'Saturday'],
            'narrow day' => [5, 'S'],
            'two letter day' => [6, 'Sa'],
        ];
    }

    #[DataProvider('quarter_format_provider')]
    public function test_quarter_transformer(int $length, string $expected): void
    {
        $transformer = new QuarterTransformer();

        $this->assertSame($expected, $transformer->format($this->date_time, $length));
    }

    public static function quarter_format_provider(): array
    {
        return [
            'single digit quarter' => [1, '2'],
            'padded quarter' => [2, '02'],
            'short quarter' => [3, 'Q2'],
            'full quarter' => [4, '2nd quarter'],
        ];
    }

    public function test_day_of_year_transformer(): void
    {
        $transformer = new DayOfYearTransformer();

        $this->assertSame('167', $transformer->format($this->date_time, 3));
        $this->assertSame('\d{3}', $transformer->getReverseMatchingRegExp(3));
    }

    public function test_reverse_matching_reg_exp_samples(): void
    {
        $year = new YearTransformer();
        $month = new MonthTransformer();

        $this->assertSame('\d{2}', $year->getReverseMatchingRegExp(2));
        $this->assertSame('\d{1,4}', $year->getReverseMatchingRegExp(4));
        $this->assertSame('\d{1,2}', $month->getReverseMatchingRegExp(1));
        $this->assertStringContainsString('Jun', $month->getReverseMatchingRegExp(3));
    }

    public function test_hour1201_and_hour2401_transformers(): void
    {
        $hour1201 = new Hour1201Transformer();
        $hour2401 = new Hour2401Transformer();

        $this->assertSame('2', $hour1201->format($this->date_time, 1));
        $this->assertSame('14', $hour2401->format($this->date_time, 2));
        $this->assertSame(15, $hour1201->normalizeHour(3, 'PM'));
        $this->assertSame(0, $hour2401->normalizeHour(24, null));

        $midnight = new DateTime('2024-06-15 00:15:00');
        $this->assertSame('24', $hour2401->format($midnight, 2));
    }

    public function test_timezone_transformer_formats_utc_and_gmt(): void
    {
        $transformer = new TimezoneTransformer();
        $utc = new DateTime('2024-06-15 12:00:00', new DateTimeZone('UTC'));

        $this->assertSame('UTC', $transformer->format($utc, 3));
        $this->assertSame(
            'Etc/GMT-5',
            TimezoneTransformer::getEtcTimeZoneId('GMT+05:00')
        );
    }

    public function test_full_transformer_formats_pattern(): void
    {
        $transformer = new FullTransformer('yyyy-MM-dd', 'UTC');
        $date_time = new DateTime('2024-06-15 14:30:45', new DateTimeZone('UTC'));

        $this->assertSame('2024-06-15', $transformer->format($date_time));
    }
}
