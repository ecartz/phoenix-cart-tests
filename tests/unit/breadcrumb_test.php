<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit;

use breadcrumb;
use PhoenixCart\Tests\support\phoenix_test_case;

final class breadcrumb_test extends phoenix_test_case
{
    public function test_reset_clears_trail(): void {
        $trail = new breadcrumb();
        $trail->add('Home', '/');
        $trail->reset();

        $this->assertSame([], $trail->trail());
    }

    public function test_add_appends_entries_in_order(): void {
        $trail = new breadcrumb();
        $trail->add('Home', '/');
        $trail->add('Catalog', '/catalog');

        $this->assertSame(
            [
                ['title' => 'Home', 'link' => '/'],
                ['title' => 'Catalog', 'link' => '/catalog'],
            ],
            $trail->trail()
        );
    }

    public function test_prepend_inserts_at_front(): void {
        $trail = new breadcrumb();
        $trail->add('Catalog', '/catalog');
        $trail->prepend('Home', '/');

        $this->assertSame(
            [
                ['title' => 'Home', 'link' => '/'],
                ['title' => 'Catalog', 'link' => '/catalog'],
            ],
            $trail->trail()
        );
    }
}
