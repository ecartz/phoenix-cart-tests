<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class PhoenixTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('PHOENIX_TEST_RUNNING')) {
            define('PHOENIX_TEST_RUNNING', true);
        }
    }
}
