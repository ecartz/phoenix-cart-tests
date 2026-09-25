<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Support;

use default_template;
use hooks;
use Linker;
use Template;

/**
 * Integration tests for content modules that call {@see execute()} with real {@see Database}.
 */
abstract class mysql_content_module_test_case extends mysql_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION' => 'Bootstrap row note',
            'BOOTSTRAP_CONTENT' => 8,
        ]);

        $GLOBALS['Template'] = new Template(new default_template());
        $GLOBALS['all_hooks'] ??= $GLOBALS['hooks'] ?? new hooks('shop');
    }

    /**
     * @param array<string, mixed> $constants
     */
    protected function define_constants(array $constants): void
    {
        foreach ($constants as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    protected function with_linker(string $prefix = 'https://shop.example.com/'): void
    {
        $this->define_constants([
            'HTTP_SERVER' => 'https://shop.example.com',
            'DIR_WS_CATALOG' => '/',
            'SESSION_FORCE_COOKIE_USE' => 'False',
        ]);

        $_SERVER['SCRIPT_NAME'] ??= '/index.php';
        $GLOBALS['Linker'] = new Linker($prefix);
    }

    protected function execute_module(string $class): void
    {
        $previous_directory = getcwd();
        $buffer_level = ob_get_level();
        chdir(DIR_FS_CATALOG);

        try {
            $module = new $class();
            $module->execute();
        } catch (\Throwable $exception) {
            while (ob_get_level() > $buffer_level) {
                ob_end_clean();
            }

            throw $exception;
        } finally {
            chdir($previous_directory);
        }
    }
}
