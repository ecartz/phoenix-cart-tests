<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Template;

use default_template;
use PhoenixCart\Tests\Support\PhoenixTestCase;

final class DefaultTemplateGridTest extends PhoenixTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BOOTSTRAP_CONTENT')) {
            define('BOOTSTRAP_CONTENT', 8);
        }
    }

    public function testGridContentWidthDefaultsToBootstrapContent(): void
    {
        $template = new default_template();

        $this->assertSame(8, $template->getGridContentWidth());
    }

    public function testSetGridContentWidthUpdatesValue(): void
    {
        $template = new default_template();
        $template->setGridContentWidth(10);

        $this->assertSame(10, $template->getGridContentWidth());
    }

    public function testGridColumnWidthIsHalfOfRemainingColumns(): void
    {
        $template = new default_template();

        $this->assertSame(2, $template->getGridColumnWidth());
    }
}
