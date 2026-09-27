<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\template;

use default_template;
use PhoenixCart\Tests\support\phoenix_test_case;

final class default_template_grid_test extends phoenix_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }
    }

    public function test_grid_content_width_defaults_to_bootstrap_content(): void
    {
        $template = new default_template();

        $this->assertSame(8, $template->getGridContentWidth());
    }

    public function test_set_grid_content_width_updates_value(): void
    {
        $template = new default_template();
        $template->setGridContentWidth(10);

        $this->assertSame(10, $template->getGridContentWidth());
    }

    public function test_grid_column_width_is_half_of_remaining_columns(): void
    {
        $template = new default_template();

        $this->assertSame(2, $template->getGridColumnWidth());
    }
}
