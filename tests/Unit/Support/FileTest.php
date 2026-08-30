<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Support;

use File;
use PhoenixCart\Tests\Support\PhoenixTestCase;

final class FileTest extends PhoenixTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/phoenix-file-test-' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*') ?: []);
            rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function testIsWritableForExistingFile(): void
    {
        $file = $this->tempDir . '/writable.txt';
        file_put_contents($file, 'test');

        $this->assertTrue(File::is_writable($file));
    }

    public function testIsWritableReturnsFalseForMissingFile(): void
    {
        $file = $this->tempDir . '/missing.txt';

        $this->assertFalse(File::is_writable($file));
    }

    public function testRemoveDeletesWritableFile(): void
    {
        $file = $this->tempDir . '/remove-me.txt';
        file_put_contents($file, 'delete');

        $this->assertTrue(File::remove($file));
        $this->assertFileDoesNotExist($file);
    }

    public function testRemoveReturnsFalseForMissingFile(): void
    {
        $file = $this->tempDir . '/already-gone.txt';

        $this->assertFalse(File::remove($file));
    }
}
