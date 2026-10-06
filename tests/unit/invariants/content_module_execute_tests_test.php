<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\invariants;

use PhoenixCart\Tests\support\phoenix_test_case;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Every catalog cm_* content module with a matching PHP class must have an execute() test.
 */
final class content_module_execute_tests_test extends phoenix_test_case {

    public function test_every_content_module_class_has_execute_test(): void {
        $catalog = rtrim(str_replace('\\', '/', DIR_FS_CATALOG), '/');
        $content_root = $catalog . '/includes/modules/content';

        if (!is_dir($content_root)) {
            $this->markTestSkipped(
                'Catalog content modules directory is missing. Clone CE-PhoenixCart per fixtures/catalog_pin.txt.'
            );
        }

        $repo = dirname(__DIR__, 3);
        $missing = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($content_root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $basename = $file->getBasename('.php');
            if (!str_starts_with($basename, 'cm_') || $basename === 'cm_template') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            $source = file_get_contents($path);
            $this->assertIsString($source);

            if (!preg_match('/\bclass\s+' . $basename . '\b/', $source)) {
                continue;
            }

            $unit = $repo . '/tests/unit/content/' . $basename . '_test.php';
            $integration = $repo . '/tests/integration/' . $basename . '_test.php';

            if (!is_file($unit) && !is_file($integration)) {
                $missing[] = $basename;
            }
        }

        sort($missing);

        $this->assertSame(
            [],
            $missing,
            'Missing execute() tests for content modules (add tests/unit/content/{class}_test.php '
            . 'or tests/integration/{class}_test.php): ' . implode(', ', $missing)
        );
    }

}
