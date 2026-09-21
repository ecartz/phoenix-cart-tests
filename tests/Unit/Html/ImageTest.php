<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use Image;

final class ImageTest extends HtmlTestCase
{
    private ?string $tempImage = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineConstantIfMissing('IMAGE_REQUIRED', 'true');
    }

    protected function tearDown(): void
    {
        if ($this->tempImage !== null && is_file($this->tempImage)) {
            unlink($this->tempImage);
        }

        parent::tearDown();
    }

    public function testToStringWithExplicitDimensions(): void
    {
        $image = new Image('banner.png', [], 'Promo banner', '120', '80');
        $image->set_prefix('/assets/');

        $this->assertSame(
            '<img src="banner.png" alt="Promo banner" width="120" height="80" class="img-fluid" title="Promo banner">',
            (string) $image
        );
    }

    public function testNormalizePathsAppliesWebPrefix(): void
    {
        $image = new Image('icons/cart.svg');
        $image->set_web_prefix('/shop/')->normalize_paths();

        $this->assertSame('/shop/icons/cart.svg', $image->get('src'));
    }

    public function testIsValidReturnsFalseForMissingFile(): void
    {
        $image = new Image('missing.png');
        $image->set_prefix(sys_get_temp_dir() . DIRECTORY_SEPARATOR)->set_default(false);

        $this->assertFalse($image->is_valid());
    }

    public function testSetResponsiveCanBeDisabled(): void
    {
        $image = new Image('logo.png', [], 'Logo', '50', '50');
        $image->set_responsive(false);

        $this->assertStringNotContainsString('img-fluid', (string) $image);
    }

    public function testSizeCalculatesDimensionsFromFile(): void
    {
        $this->tempImage = $this->createTempPng('phoenix-image-test.png');

        $image = new Image(basename($this->tempImage));
        $image->set_prefix(dirname($this->tempImage) . DIRECTORY_SEPARATOR);

        $this->assertTrue($image->size());
        $this->assertSame('1', $image->get('width'));
        $this->assertSame('1', $image->get('height'));
    }

    public function testDefaultImageUsedWhenSourceMissing(): void
    {
        $image = new Image('missing.png', [], 'Fallback', '10', '10');
        $image->set_prefix(sys_get_temp_dir() . DIRECTORY_SEPARATOR)
            ->set_default('placeholder.png');

        $this->assertStringContainsString('src="placeholder.png"', (string) $image);
    }
}
