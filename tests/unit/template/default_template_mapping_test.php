<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\template;

use default_template;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\DataProvider;

final class default_template_mapping_test extends phoenix_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }
    }

    #[DataProvider('template_mapping_provider')]
    public function test_get_template_mapping_for(string $file, string $type, string $expected_suffix): void
    {
        $result = default_template::_get_template_mapping_for($file, $type);

        $this->assertStringEndsWith($expected_suffix, str_replace('\\', '/', $result));
    }

    public static function template_mapping_provider(): array
    {
        $catalog = DIR_FS_CATALOG;

        return [
            'page type uses pages directory' => [
                'checkout_success.php',
                'page',
                'templates/default/includes/pages/checkout_success.php',
            ],
            'component type uses components directory' => [
                'header.php',
                'component',
                'templates/default/includes/components/header.php',
            ],
            'module type uses tpl prefix beside module' => [
                $catalog . 'includes/modules/content/header/cm_header_menu.php',
                'module',
                'includes/modules/content/header/templates/tpl_cm_header_menu.php',
            ],
            'ext type mirrors ext tree under template includes' => [
                'ext/modules/content/reviews/write.php',
                'ext',
                'templates/default/includes/ext/modules/content/reviews/write.php',
            ],
            'translation type uses catalog-relative path' => [
                'includes/languages/english.php',
                'translation',
                'includes/languages/english.php',
            ],
            'literal type uses template root' => [
                'css/stylesheet.css',
                'literal',
                'templates/default/css/stylesheet.css',
            ],
            'unknown type falls back to literal' => [
                'js/app.js',
                'unknown',
                'templates/default/js/app.js',
            ],
        ];
    }

    public function test_extract_relative_path_strips_catalog_root(): void
    {
        $relative = 'ext/modules/content/reviews/write.php';
        $base = rtrim(str_replace('\\', '/', DIR_FS_CATALOG), '/') . '/';
        $absolute = $base . $relative;

        $this->assertSame(
            $relative,
            default_template::extract_relative_path($absolute, $base)
        );
    }

    public function test_get_template_mapping_for_returns_existing_file(): void
    {
        $template = new default_template();

        $mapping = $template->get_template_mapping_for('index.php', 'page');

        $this->assertNotNull($mapping);
        $this->assertFileExists($mapping);
    }

    public function test_get_template_mapping_for_returns_null_for_missing_file(): void
    {
        $template = new default_template();

        $this->assertNull($template->get_template_mapping_for('does_not_exist.php', 'page'));
    }
}
