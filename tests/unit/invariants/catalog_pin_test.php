<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\invariants;

use PhoenixCart\Tests\support\phoenix_test_case;

final class catalog_pin_test extends phoenix_test_case
{
    public function test_catalog_pin_file_declares_ce_phoenixcart_tag(): void
    {
        $path = dirname(__DIR__, 3) . '/fixtures/catalog_pin.txt';

        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertMatchesRegularExpression(
            '/^CE_PHOENIXCART_TAG=\d+\.\d+\.\d+\.\d+\s*$/m',
            $contents,
            'fixtures/catalog_pin.txt must set CE_PHOENIXCART_TAG to a CE release tag (e.g. 1.1.0.8)'
        );
    }
}
