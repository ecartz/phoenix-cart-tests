<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use PhoenixCart\Tests\Support\PhoenixTestCase;

final class DefaultTemplateMappingTest extends PhoenixTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }
    }

    /**
     * @dataProvider templateMappingProvider
     */
    public function testGetTemplateMappingFor(string $file, string $type, string $expectedSuffix): void
    {
        $result = default_template::_get_template_mapping_for($file, $type);

        $this->assertStringEndsWith($expectedSuffix, str_replace('\\', '/', $result));
    }

    public static function templateMappingProvider(): array
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
                $catalog . 'ext/modules/content/reviews/write.php',
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

    public function testExtractRelativePathStripsCatalogRoot(): void
    {
        $absolute = DIR_FS_CATALOG . 'ext/modules/content/reviews/write.php';

        $this->assertSame(
            'ext/modules/content/reviews/write.php',
            default_template::extract_relative_path($absolute)
        );
    }

    public function testGetTemplateMappingForReturnsExistingFile(): void
    {
        $template = new default_template();

        $mapping = $template->get_template_mapping_for('index.php', 'page');

        $this->assertNotNull($mapping);
        $this->assertFileExists($mapping);
    }

    public function testGetTemplateMappingForReturnsNullForMissingFile(): void
    {
        $template = new default_template();

        $this->assertNull($template->get_template_mapping_for('does_not_exist.php', 'page'));
    }
}
