<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\invariants;

use PhoenixCart\Tests\support\phoenix_test_case;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Wave 5 browser specs use repo snake_case for const/let bindings (not Playwright fixture names).
 */
final class browser_spec_snake_case_test extends phoenix_test_case
{
    private const SNAKE_CASE = '/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/';

    public function test_browser_spec_const_and_let_bindings_use_snake_case(): void
    {
        $browser_dir = dirname(__DIR__, 2) . '/browser';

        $this->assertDirectoryExists($browser_dir);

        $violations = [];

        foreach ($this->typescript_files_under($browser_dir) as $file) {
            $source = file_get_contents($file);
            $this->assertIsString($source);

            foreach ($this->binding_names($source) as $name) {
                if (preg_match(self::SNAKE_CASE, $name) === 1) {
                    continue;
                }

                $relative = $this->relative_path($file);
                $violations[] = sprintf('%s: binding %s must be snake_case', $relative, $name);
            }
        }

        $this->assertSame([], $violations, implode("\n", $violations));
    }

    /**
     * @return list<string>
     */
    private function typescript_files_under(string $directory): array
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'ts') {
                continue;
            }

            $paths[] = $file->getPathname();
        }

        sort($paths);

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function binding_names(string $source): array
    {
        $names = [];

        if (preg_match_all('/\b(?:const|let)\s+([A-Za-z_][A-Za-z0-9_]*)\s*(?:=|:)/', $source, $matches) !== false) {
            foreach ($matches[1] as $name) {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function relative_path(string $absolute): string
    {
        $repo_root = dirname(__DIR__, 3);

        return str_replace('\\', '/', substr($absolute, strlen($repo_root) + 1));
    }
}
