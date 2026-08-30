<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use cc_validation;
use PhoenixCart\Tests\Support\PhoenixTestCase;

final class CcValidationTest extends PhoenixTestCase
{
    /**
     * @dataProvider validCardProvider
     */
    public function testValidateAcceptsValidCards(
        string $number,
        string $expectedType,
        int $expiryMonth,
        int $expiryYearSuffix
    ): void {
        $validator = new cc_validation();
        $result = $validator->validate($number, $expiryMonth, $expiryYearSuffix);

        $this->assertTrue($result);
        $this->assertSame($expectedType, $validator->cc_type);
        $this->assertSame($expiryMonth, (int) $validator->cc_expiry_month);
    }

    public static function validCardProvider(): array
    {
        return [
            'visa' => ['4111-1111-1111-1111', 'Visa', 12, 30],
            'mastercard' => ['5555-5555-5555-4444', 'Master Card', 6, 28],
            'american express' => ['3782-822463-10005', 'American Express', 3, 29],
            'discover' => ['6011-1111-1111-1117', 'Discover', 1, 31],
        ];
    }

    /**
     * @dataProvider invalidCardProvider
     */
    public function testValidateRejectsInvalidCards(
        string $number,
        int $expiryMonth,
        int $expiryYearSuffix,
        int $expectedCode
    ): void {
        $validator = new cc_validation();

        $this->assertSame($expectedCode, $validator->validate($number, $expiryMonth, $expiryYearSuffix));
    }

    public static function invalidCardProvider(): array
    {
        $currentYearSuffix = (int) substr((string) date('Y'), 2, 2);

        return [
            'unknown card type' => ['1234567890123456', 12, 30, -1],
            'invalid month zero' => ['4111111111111111', 0, 30, -2],
            'invalid month thirteen' => ['4111111111111111', 13, 30, -2],
            'expiry year too far ahead' => ['4111111111111111', 12, 50, -3],
            'expired card this year' => ['4111111111111111', 1, $currentYearSuffix, -4],
        ];
    }

    public function testValidateRejectsFailedLuhnCheck(): void
    {
        $validator = new cc_validation();

        $this->assertFalse($validator->validate('4111111111111112', 12, 30));
    }

    public function testValidateStripsNonDigits(): void
    {
        $validator = new cc_validation();
        $validator->validate('4111 1111-1111 1111', 12, 30);

        $this->assertSame('4111111111111111', $validator->cc_number);
    }

    /**
     * @dataProvider luhnProvider
     */
    public function testIsValidLuhnCheck(string $number, bool $expected): void
    {
        $validator = new cc_validation();
        $validator->cc_number = preg_replace('/[^0-9]/', '', $number);

        $this->assertSame($expected, $validator->is_valid());
    }

    public static function luhnProvider(): array
    {
        return [
            'valid visa' => ['4111111111111111', true],
            'invalid checksum' => ['4111111111111112', false],
        ];
    }
}
