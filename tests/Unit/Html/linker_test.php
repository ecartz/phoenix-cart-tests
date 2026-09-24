<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use Href;
use Linker;

final class linker_test extends html_test_case {

    public function test_build_returns_href_with_prefix(): void {
        $linker = new Linker('https://shop.example.com/catalog/');
        $href = $linker->build('index.php', ['cPath' => '1_2']);

        $this->assertInstanceOf(Href::class, $href);
        $this->assertStringContainsString('https://shop.example.com/catalog/', "$href");
        $this->assertStringContainsString('index.php', "$href");
    }

    public function test_prefix_mutator(): void {
        $linker = new Linker('/base/');
        $linker->set_prefix('/other/');

        $this->assertSame('/other/', $linker->get_prefix());
    }

}
