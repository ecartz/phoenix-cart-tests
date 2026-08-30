<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use PhoenixCart\Tests\Support\PhoenixTestCase;

abstract class HtmlTestCase extends PhoenixTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->defineConstantIfMissing('DIR_WS_CATALOG', '/');
        $this->defineConstantIfMissing('TEXT_FIELD_REQUIRED', '<span class="required">*</span>');
    }

    protected function defineConstantIfMissing(string $name, string $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * @return object{chain: callable}
     */
    protected function createHrefHooks(): object
    {
        return new class {
            public function chain(string $action, array $parameters): array
            {
                return \Href::hook($parameters);
            }
        };
    }

    protected function createTempPng(string $basename = 'test.png'): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $basename;
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        file_put_contents($path, $png);

        return $path;
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];

        parent::tearDown();
    }
}
