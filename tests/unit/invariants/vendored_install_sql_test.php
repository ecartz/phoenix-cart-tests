<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\invariants;

use PhoenixCart\Tests\support\phoenix_test_case;

/**
 * Vendored install SQL must match the catalog checkout used by the suite.
 *
 * The shared "# $Id$" header is boilerplate, not a CE ref, so this compares
 * full file bytes. It does not fetch the pin; CI clones fixtures/catalog_pin.txt
 * into PHOENIX_CART_ROOT first. A CRLF checkout fails this check.
 */
final class vendored_install_sql_test extends phoenix_test_case {

    public function test_vendored_install_sql_matches_catalog_checkout(): void {
        $repo = dirname(__DIR__, 3);
        $catalog = rtrim(str_replace('\\', '/', DIR_FS_CATALOG), '/');

        $pairs = [
            'fixtures/phoenix.sql' => 'install/phoenix.sql',
            'fixtures/phoenix_data_sample.sql' => 'install/phoenix_data_sample.sql',
        ];

        foreach ($pairs as $vendored => $relative) {
            $this->assert_same_bytes(
                $repo . '/' . $vendored,
                $catalog . '/' . $relative,
                $vendored,
                $relative
            );
        }
    }

    private function assert_same_bytes(string $fixture, string $upstream, string $vendored, string $relative): void {
        $this->assertFileExists($fixture);
        $this->assertFileExists(
            $upstream,
            $relative . ' is missing from the catalog checkout. Clone the ref in fixtures/catalog_pin.txt.'
        );

        $fixture_bytes = file_get_contents($fixture);
        $upstream_bytes = file_get_contents($upstream);
        $this->assertIsString($fixture_bytes);
        $this->assertIsString($upstream_bytes);

        $this->assertSame(
            hash('sha256', $upstream_bytes),
            hash('sha256', $fixture_bytes),
            sprintf(
                '%s (%d bytes) must match %s (%d bytes) in the catalog checkout byte for byte. '
                . 'Copy both install SQL files from that checkout (see fixtures/README.md). '
                . 'The shared # $Id$ header is not a version marker.',
                $vendored,
                strlen($fixture_bytes),
                $relative,
                strlen($upstream_bytes)
            )
        );
    }

}
