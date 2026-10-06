<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_i_slider;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;
use Text;

#[Group('mysql')]
final class cm_i_slider_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $_SESSION['languages_id'] = 1;
        $this->with_linker();
    }

    public function test_execute_buffers_slider_when_advert_group_configured(): void {
        if (!defined('MODULE_CONTENT_I_SLIDER_GRP') || Text::is_empty(MODULE_CONTENT_I_SLIDER_GRP)) {
            $this->markTestSkipped('MODULE_CONTENT_I_SLIDER_GRP is not set in fixture configuration.');
        }

        $this->execute_module(cm_i_slider::class);

        $content = $this->buffered_content('index');
        $this->assertStringContainsString('cm-i-slider', $content);
    }

}
