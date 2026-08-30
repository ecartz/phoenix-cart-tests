<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit;

use breadcrumb;
use PhoenixCart\Tests\Support\PhoenixTestCase;

final class BreadcrumbTest extends PhoenixTestCase
{
    public function testResetClearsTrail(): void
    {
        $trail = new breadcrumb();
        $trail->add('Home', '/');
        $trail->reset();

        $this->assertSame([], $trail->trail());
    }

    public function testAddAppendsEntriesInOrder(): void
    {
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

    public function testPrependInsertsAtFront(): void
    {
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
