<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\html;

use Image;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class image_test extends html_test_case {

    private ?string $temp_image = null;

    protected function setUp(): void {
        parent::setUp();

        $this->define_constant_if_missing('IMAGE_REQUIRED', 'true');
    }

    protected function tearDown(): void {
        if ($this->temp_image !== null && is_file($this->temp_image)) {
            unlink($this->temp_image);
        }

        parent::tearDown();
    }

    public function test_to_string_with_explicit_dimensions(): void {
        $image = new Image('banner.png', [], 'Promo banner', '120', '80');
        $image->set_prefix('/assets/');

        $this->assertSame(
            '<img src="banner.png" alt="Promo banner" width="120" height="80" class="img-fluid" title="Promo banner">',
            "$image"
        );
    }

    public function test_normalize_paths_applies_web_prefix(): void {
        $image = new Image('icons/cart.svg');
        $image->set_web_prefix('/shop/')->normalize_paths();

        $this->assertSame('/shop/icons/cart.svg', $image->get('src'));
    }

    public function test_is_valid_returns_false_for_missing_file(): void {
        $image = new Image('missing.png');
        $image->set_prefix(sys_get_temp_dir() . DIRECTORY_SEPARATOR)->set_default(false);

        $this->assertFalse($image->is_valid());
    }

    public function test_set_responsive_can_be_disabled(): void {
        $image = new Image('logo.png', [], 'Logo', '50', '50');
        $image->set_responsive(false);

        $this->assertStringNotContainsString('img-fluid', "$image");
    }

    public function test_size_calculates_dimensions_from_file(): void {
        $this->temp_image = $this->create_temp_png('phoenix-image-test.png');

        $image = new Image(basename($this->temp_image));
        $image->set_prefix(dirname($this->temp_image) . DIRECTORY_SEPARATOR);

        $this->assertTrue($image->size());
        $this->assertSame('1', $image->get('width'));
        $this->assertSame('1', $image->get('height'));
    }

    public function test_default_image_used_when_source_missing(): void {
        $image = new Image('missing.png', [], 'Fallback', '10', '10');
        $image->set_prefix(sys_get_temp_dir() . DIRECTORY_SEPARATOR)
            ->set_default('placeholder.png');

        $this->assertStringContainsString('src="placeholder.png"', "$image");
    }

}

final class image_required_false_test extends phoenix_test_case {

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_missing_image_renders_empty_when_not_required(): void {
        define('IMAGE_REQUIRED', 'false');

        $image = new Image('missing.png');
        $image->set_prefix(sys_get_temp_dir() . DIRECTORY_SEPARATOR)->set_default(false);

        $this->assertSame('', "$image");
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_size_treats_missing_file_as_valid_when_not_required(): void {
        define('IMAGE_REQUIRED', 'false');

        $image = new Image('missing.png');
        $image->set_prefix(sys_get_temp_dir() . DIRECTORY_SEPARATOR)->set_default(false);

        $this->assertTrue($image->size());
    }

}
