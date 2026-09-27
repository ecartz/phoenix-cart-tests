<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use Path;
use PhoenixCart\Tests\support\phoenix_test_case;

final class path_test extends phoenix_test_case
{
    private string $temp_dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temp_dir = sys_get_temp_dir() . '/phoenix-path-test-' . uniqid('', true);
        mkdir($this->temp_dir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->temp_dir)) {
            $this->remove_directory($this->temp_dir);
        }

        parent::tearDown();
    }

    public function test_normalize_converts_backslashes(): void
    {
        $subdir = $this->temp_dir . '/nested';
        mkdir($subdir);

        $normalized = Path::normalize($subdir);

        $this->assertStringNotContainsString('\\', $normalized);

        $expected = realpath($subdir);
        $this->assertNotFalse($expected);
        $this->assertSame(Path::normalize($expected), $normalized);
    }

    public function test_is_writable_for_writable_directory(): void
    {
        $this->assertTrue(Path::is_writable($this->temp_dir));
    }

    public function test_is_writable_for_writable_file(): void
    {
        $file = $this->temp_dir . '/file.txt';
        file_put_contents($file, 'content');

        $this->assertTrue(Path::is_writable($file));
    }

    public function test_remove_deletes_directory_tree(): void
    {
        $nested = $this->temp_dir . '/parent/child';
        mkdir($nested, 0777, true);
        file_put_contents($nested . '/leaf.txt', 'leaf');

        $this->assertTrue(Path::remove($this->temp_dir . '/parent'));
        $this->assertDirectoryDoesNotExist($this->temp_dir . '/parent');
    }

    public function test_remove_deletes_single_file(): void
    {
        $file = $this->temp_dir . '/single.txt';
        file_put_contents($file, 'single');

        $this->assertTrue(Path::remove($file));
        $this->assertFileDoesNotExist($file);
    }

    private function remove_directory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $full_path = $path . '/' . $entry;
            if (is_dir($full_path)) {
                $this->remove_directory($full_path);
            } else {
                unlink($full_path);
            }
        }

        rmdir($path);
    }
}
