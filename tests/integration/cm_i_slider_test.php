<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_i_slider;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_i_slider_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/index/cm_i_slider.php',
            'MODULE_CONTENT_I_SLIDER_TITLE'
        );
    }

    public function test_execute_renders_sample_carousel_advert(): void {
        $this->assertSame('carousel', MODULE_CONTENT_I_SLIDER_GRP);

        $this->execute_module(cm_i_slider::class);

        $content = $this->buffered_content('index');
        $this->assertStringContainsString('cm-i-slider', $content);
        $this->assertStringContainsString('Fresh fruit direct to your door', $content);
    }

}
