<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use Password;
use PasswordHash;
use PhoenixCart\Tests\Support\phoenix_test_case;
use PHPUnit\Framework\Attributes\DataProvider;

final class password_test extends phoenix_test_case
{
    #[DataProvider('type_provider')]
    public function test_type(string $hashed, string $expected): void
    {
        $this->assertSame($expected, Password::type($hashed));
    }

    public static function type_provider(): array
    {
        return [
            'salt format' => ['0123456789abcdef0123456789abcdef:ab', 'salt'],
            'phpass format' => ['$P$9abcdefghijklmnopqrstuv', 'phpass'],
            'native bcrypt' => ['$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUV', 'native'],
        ];
    }

    public function test_validate_rejects_empty_values(): void
    {
        $hashed = Password::hash('secret');

        $this->assertFalse(Password::validate('', $hashed));
        $this->assertFalse(Password::validate('secret', ''));
    }

    public function test_validate_native_hash(): void
    {
        $plain = 'phoenix-test-password';
        $hashed = Password::hash($plain);

        $this->assertTrue(Password::validate($plain, $hashed));
        $this->assertFalse(Password::validate('wrong-password', $hashed));
    }

    public function test_validate_salt_hash(): void
    {
        $plain = 'legacy-password';
        $salt = 'ab';
        $hashed = md5($salt . $plain) . ':' . $salt;

        $this->assertSame('salt', Password::type($hashed));
        $this->assertTrue(Password::validate($plain, $hashed));
        $this->assertFalse(Password::validate('wrong-password', $hashed));
    }

    public function test_validate_phpass_hash(): void
    {
        $plain = 'phpass-password';
        $hasher = new PasswordHash(10, true);
        $hashed = $hasher->HashPassword($plain);

        $this->assertSame('phpass', Password::type($hashed));
        $this->assertTrue(Password::validate($plain, $hashed));
        $this->assertFalse(Password::validate('wrong-password', $hashed));
    }

    public function test_needs_rehash_for_legacy_formats(): void
    {
        $salt_hash = md5('absalt') . ':ab';
        $hasher = new PasswordHash(10, true);
        $phpass_hash = $hasher->HashPassword('legacy');

        $this->assertTrue(Password::needs_rehash($salt_hash));
        $this->assertTrue(Password::needs_rehash($phpass_hash));
    }

    public function test_needs_rehash_for_fresh_native_hash(): void
    {
        $hashed = Password::hash('current-password');

        $this->assertFalse(Password::needs_rehash($hashed));
    }

    #[DataProvider('create_random_provider')]
    public function test_create_random(int $length, string $type): void
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

    public static function create_random_provider(): array
    {
        return [
            'mixed characters' => [16, 'mixed'],
            'letters only' => [12, 'letters'],
            'digits only' => [8, 'digits'],
        ];
    }

    public function test_create_random_falls_back_to_mixed_for_unknown_type(): void
    {
        $value = Password::create_random(10, 'unknown-type');

        $this->assertSame(10, strlen($value));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $value);
    }

    public function test_get_algorithm_defaults_to_password_default(): void
    {
        if (defined('PHOENIX_ENCRYPTION')) {
            $this->markTestSkipped('PHOENIX_ENCRYPTION is already defined in this process.');
        }

        $this->assertSame(PASSWORD_DEFAULT, Password::get_algorithm());
    }
}
