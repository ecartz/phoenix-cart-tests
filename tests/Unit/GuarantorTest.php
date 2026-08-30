<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit;

use Guarantor;
use PhoenixCart\Tests\Support\PhoenixTestCase;

final class GuarantorTest extends PhoenixTestCase
{
    public function testGuaranteeSubarrayCreatesMissingArray(): void
    {
        $data = [];

        $subarray = &Guarantor::guarantee_subarray($data, 'items');

        $this->assertIsArray($subarray);
        $this->assertSame([], $subarray);
        $this->assertArrayHasKey('items', $data);
        $this->assertSame([], $data['items']);
    }

    public function testGuaranteeSubarrayReplacesNonArrayValue(): void
    {
        $data = ['items' => 'not-an-array'];

        $subarray = &Guarantor::guarantee_subarray($data, 'items');

        $this->assertIsArray($subarray);
        $this->assertSame([], $subarray);
    }

    public function testGuaranteeAllBuildsNestedStructure(): void
    {
        $data = [];

        $leaf = &Guarantor::guarantee_all($data, 'catalog', 'products', 'featured');

        $leaf[] = 42;

        $this->assertSame([42], $data['catalog']['products']['featured']);
    }

    public function testEnsureGlobalCreatesSingleton(): void
    {
        $first = &Guarantor::ensure_global(\stdClass::class);
        $second = &Guarantor::ensure_global(\stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $first);
        $this->assertSame($first, $second);
    }

    public function testDeprecatedWrappersDelegateToGuarantor(): void
    {
        if (!function_exists('tep_guarantee_subarray')) {
            $this->markTestSkipped('tep_guarantee_subarray is not loaded in this Phoenix Cart build.');
        }

        $this->expectUserDeprecationMessage('The tep_guarantee_subarray function has been deprecated.');

        $data = [];
        $subarray = &tep_guarantee_subarray($data, 'legacy');

        $this->assertSame([], $subarray);
        $this->assertArrayHasKey('legacy', $data);
    }
}
