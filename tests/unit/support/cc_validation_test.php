<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use cc_validation;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\DataProvider;

final class cc_validation_test extends phoenix_test_case {

    #[DataProvider('valid_card_provider')]
    public function test_validate_accepts_valid_cards(
        string $number,
        string $expected_type,
        int $expiry_month,
        int $expiry_year_suffix
    ): void {
        $validator = new cc_validation();
        $result = $validator->validate($number, $expiry_month, $expiry_year_suffix);

        $this->assertTrue($result);
        $this->assertSame($expected_type, $validator->cc_type);
        $this->assertSame($expiry_month, (int) $validator->cc_expiry_month);
    }

    public static function valid_card_provider(): array {
        return [
            'visa' => ['4111-1111-1111-1111', 'Visa', 12, 30],
            'mastercard' => ['5555-5555-5555-4444', 'Master Card', 6, 28],
            'american express' => ['3782-822463-10005', 'American Express', 3, 29],
            'discover' => ['6011-1111-1111-1117', 'Discover', 1, 31],
        ];
    }

    #[DataProvider('invalid_card_provider')]
    public function test_validate_rejects_invalid_cards(
        string $number,
        int $expiry_month,
        int $expiry_year_suffix,
        int $expected_code
    ): void {
        $validator = new cc_validation();

        $this->assertSame($expected_code, $validator->validate($number, $expiry_month, $expiry_year_suffix));
    }

    public static function invalid_card_provider(): array {
        $current_year_suffix = (int) substr((string) date('Y'), 2, 2);

        return [
            'unknown card type' => ['1234567890123456', 12, 30, -1],
            'invalid month zero' => ['4111111111111111', 0, 30, -2],
            'invalid month thirteen' => ['4111111111111111', 13, 30, -2],
            'expiry year too far ahead' => ['4111111111111111', 12, 50, -3],
            'expired card this year' => ['4111111111111111', 1, $current_year_suffix, -4],
        ];
    }

    public function test_validate_rejects_failed_luhn_check(): void {
        $validator = new cc_validation();

        $this->assertFalse($validator->validate('4111111111111112', 12, 30));
    }

    public function test_validate_strips_non_digits(): void {
        $validator = new cc_validation();
        $validator->validate('4111 1111-1111 1111', 12, 30);

        $this->assertSame('4111111111111111', $validator->cc_number);
    }

    #[DataProvider('luhn_provider')]
    public function test_is_valid_luhn_check(string $number, bool $expected): void {
        $validator = new cc_validation();
        $validator->cc_number = preg_replace('/[^0-9]/', '', $number);

        $this->assertSame($expected, $validator->is_valid());
    }

    public static function luhn_provider(): array {
        return [
            'valid visa' => ['4111111111111111', true],
            'invalid checksum' => ['4111111111111112', false],
        ];
    }

}
