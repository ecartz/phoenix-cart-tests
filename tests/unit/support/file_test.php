<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use File;
use PhoenixCart\Tests\support\phoenix_test_case;

final class file_test extends phoenix_test_case
{
    private string $temp_dir;

    protected function setUp(): void {
        parent::setUp();

        $this->temp_dir = sys_get_temp_dir() . '/phoenix-file-test-' . uniqid('', true);
        mkdir($this->temp_dir, 0777, true);
    }

    protected function tearDown(): void {
        if (is_dir($this->temp_dir)) {
            array_map('unlink', glob($this->temp_dir . '/*') ?: []);
            rmdir($this->temp_dir);
        }

        parent::tearDown();
    }

    public function test_is_writable_for_existing_file(): void {
        $file = $this->temp_dir . '/writable.txt';
        file_put_contents($file, 'test');

        $this->assertTrue(File::is_writable($file));
    }

    public function test_is_writable_returns_false_for_missing_file(): void {
        $file = $this->temp_dir . '/missing.txt';

        $this->assertFalse(File::is_writable($file));
    }

    public function test_remove_deletes_writable_file(): void {
        $file = $this->temp_dir . '/remove-me.txt';
        file_put_contents($file, 'delete');

        $this->assertTrue(File::remove($file));
        $this->assertFileDoesNotExist($file);
    }

    public function test_remove_returns_false_for_missing_file(): void {
        $file = $this->temp_dir . '/already-gone.txt';

        $this->assertFalse(File::remove($file));
    }
}
