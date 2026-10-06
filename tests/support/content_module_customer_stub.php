<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Minimal {@see customer} stand-in for account content-module unit tests.
 */
final class content_module_customer_stub {

    public function __construct(
        private readonly string $country_id = '223',
        private readonly string $password = '',
    ) {
    }

    public function get(string $key): mixed {
        return match ($key) {
            'country_id' => $this->country_id,
            'password' => $this->password,
            default => null,
        };
    }

}
