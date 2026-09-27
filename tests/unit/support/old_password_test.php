<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use old_password;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\DataProvider;

final class old_password_test extends phoenix_test_case
{
    #[DataProvider('validate_provider')]
    public function test_validate(string $plain, string $encrypted, bool $expected): void
    {
        $this->assertSame($expected, old_password::validate($plain, $encrypted));
    }

    public static function validate_provider(): array
    {
        $salt = 'test-salt';
        $plain = 'secret';
        $valid = md5($salt . $plain) . ':' . $salt;

        return [
            'matching md5 salted hash' => [$plain, $valid, true],
            'wrong plain text' => ['wrong', $valid, false],
            'empty plain' => ['', $valid, false],
            'empty encrypted' => [$plain, '', false],
            'wrong hash with valid salt format' => [$plain, md5($salt . 'other') . ':' . $salt, false],
        ];
    }
}
