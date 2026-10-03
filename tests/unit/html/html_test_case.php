<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\html;

use PhoenixCart\Tests\support\phoenix_test_case;

abstract class html_test_case extends phoenix_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constant_if_missing('HTTP_SERVER', 'https://shop.example.com');
        $this->define_constant_if_missing('DIR_WS_CATALOG', '/');
        $this->define_constant_if_missing('TEXT_FIELD_REQUIRED', '<span class="required">*</span>');
        $this->define_constant_if_missing('SESSION_FORCE_COOKIE_USE', 'False');

        $GLOBALS['all_hooks'] ??= $GLOBALS['hooks'] ?? new \hooks('shop');
    }

    protected function define_constant_if_missing(string $name, string $value): void {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * @return object{chain: callable}
     */
    protected function create_href_hooks(): object {
        return new class {
            public function chain(string $action, array $parameters): array {
                return \Href::hook($parameters);
            }
        };
    }

    protected function create_temp_png(string $basename = 'test.png'): string {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $basename;
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        file_put_contents($path, $png);

        return $path;
    }

    protected function tearDown(): void {
        $_GET = [];
        $_POST = [];

        parent::tearDown();
    }

}
