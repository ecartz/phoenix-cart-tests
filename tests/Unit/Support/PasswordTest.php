<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use Password;
use PasswordHash;
use PhoenixCart\Tests\Support\PhoenixTestCase;

final class PasswordTest extends PhoenixTestCase
{
    /**
     * @dataProvider typeProvider
     */
    public function testType(string $hashed, string $expected): void
    {
        $this->assertSame($expected, Password::type($hashed));
    }

    public static function typeProvider(): array
    {
        return [
            'salt format' => ['0123456789abcdef0123456789abcdef:ab', 'salt'],
            'phpass format' => ['$P$Babcdefghijklmnopqrstuv', 'phpass'],
            'native bcrypt' => ['$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUV', 'native'],
        ];
    }

    public function testValidateRejectsEmptyValues(): void
    {
        $hashed = Password::hash('secret');

        $this->assertFalse(Password::validate('', $hashed));
        $this->assertFalse(Password::validate('secret', ''));
    }

    public function testValidateNativeHash(): void
    {
        $plain = 'phoenix-test-password';
        $hashed = Password::hash($plain);

        $this->assertTrue(Password::validate($plain, $hashed));
        $this->assertFalse(Password::validate('wrong-password', $hashed));
    }

    public function testValidateSaltHash(): void
    {
        $plain = 'legacy-password';
        $salt = 'ab';
        $hashed = md5($salt . $plain) . ':' . $salt;

        $this->assertSame('salt', Password::type($hashed));
        $this->assertTrue(Password::validate($plain, $hashed));
        $this->assertFalse(Password::validate('wrong-password', $hashed));
    }

    public function testValidatePhpassHash(): void
    {
        $plain = 'phpass-password';
        $hasher = new PasswordHash(10, true);
        $hashed = $hasher->HashPassword($plain);

        $this->assertSame('phpass', Password::type($hashed));
        $this->assertTrue(Password::validate($plain, $hashed));
        $this->assertFalse(Password::validate('wrong-password', $hashed));
    }

    public function testNeedsRehashForLegacyFormats(): void
    {
        $saltHash = md5('absalt') . ':ab';
        $hasher = new PasswordHash(10, true);
        $phpassHash = $hasher->HashPassword('legacy');

        $this->assertTrue(Password::needs_rehash($saltHash));
        $this->assertTrue(Password::needs_rehash($phpassHash));
    }

    public function testNeedsRehashForFreshNativeHash(): void
    {
        $hashed = Password::hash('current-password');

        $this->assertFalse(Password::needs_rehash($hashed));
    }

    /**
     * @dataProvider createRandomProvider
     */
    public function testCreateRandom(int $length, string $type): void
    {
        $value = Password::create_random($length, $type);

        $this->assertSame($length, strlen($value));

        if ($type === 'digits') {
            $this->assertMatchesRegularExpression('/^\d+$/', $value);
        } elseif ($type === 'letters') {
            $this->assertMatchesRegularExpression('/^[A-Za-z]+$/', $value);
        } else {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $value);
        }
    }

    public static function createRandomProvider(): array
    {
        return [
            'mixed characters' => [16, 'mixed'],
            'letters only' => [12, 'letters'],
            'digits only' => [8, 'digits'],
        ];
    }

    public function testCreateRandomFallsBackToMixedForUnknownType(): void
    {
        $value = Password::create_random(10, 'unknown-type');

        $this->assertSame(10, strlen($value));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $value);
    }
}
