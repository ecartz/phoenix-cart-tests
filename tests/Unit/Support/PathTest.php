<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use Path;
use PhoenixCart\Tests\Support\PhoenixTestCase;

final class PathTest extends PhoenixTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/phoenix-path-test-' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    public function testNormalizeConvertsBackslashes(): void
    {
        $subdir = $this->tempDir . '/nested';
        mkdir($subdir);

        $normalized = Path::normalize($subdir);

        $this->assertStringNotContainsString('\\', $normalized);
        $this->assertSame(realpath($subdir), $normalized);
    }

    public function testIsWritableForWritableDirectory(): void
    {
        $this->assertTrue(Path::is_writable($this->tempDir));
    }

    public function testIsWritableForWritableFile(): void
    {
        $file = $this->tempDir . '/file.txt';
        file_put_contents($file, 'content');

        $this->assertTrue(Path::is_writable($file));
    }

    public function testRemoveDeletesDirectoryTree(): void
    {
        $nested = $this->tempDir . '/parent/child';
        mkdir($nested, 0777, true);
        file_put_contents($nested . '/leaf.txt', 'leaf');

        $this->assertTrue(Path::remove($this->tempDir . '/parent'));
        $this->assertDirectoryDoesNotExist($this->tempDir . '/parent');
    }

    public function testRemoveDeletesSingleFile(): void
    {
        $file = $this->tempDir . '/single.txt';
        file_put_contents($file, 'single');

        $this->assertTrue(Path::remove($file));
        $this->assertFileDoesNotExist($file);
    }

    private function removeDirectory(string $path): void
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

            $fullPath = $path . '/' . $entry;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
            } else {
                unlink($fullPath);
            }
        }

        rmdir($path);
    }
}
