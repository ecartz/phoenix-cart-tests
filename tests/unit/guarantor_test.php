<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit;

use Guarantor;
use PhoenixCart\Tests\support\phoenix_test_case;

final class guarantor_test extends phoenix_test_case
{
    public function test_guarantee_subarray_creates_missing_array(): void {
        $data = [];

        $subarray = &Guarantor::guarantee_subarray($data, 'items');

        $this->assertIsArray($subarray);
        $this->assertSame([], $subarray);
        $this->assertArrayHasKey('items', $data);
        $this->assertSame([], $data['items']);
    }

    public function test_guarantee_subarray_replaces_non_array_value(): void {
        $data = ['items' => 'not-an-array'];

        $subarray = &Guarantor::guarantee_subarray($data, 'items');

        $this->assertIsArray($subarray);
        $this->assertSame([], $subarray);
    }

    public function test_guarantee_all_builds_nested_structure(): void {
        $data = [];

        $leaf = &Guarantor::guarantee_all($data, 'catalog', 'products', 'featured');

        $leaf[] = 42;

        $this->assertSame([42], $data['catalog']['products']['featured']);
    }

    public function test_ensure_global_creates_singleton(): void {
        $first = &Guarantor::ensure_global(\stdClass::class);
        $second = &Guarantor::ensure_global(\stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $first);
        $this->assertSame($first, $second);
    }

    public function test_deprecated_wrappers_are_absent_or_delegate_to_guarantor(): void {
        if (!function_exists('tep_guarantee_subarray')) {
            $this->assertFalse(function_exists('tep_guarantee_all'));

            return;
        }

        $this->expectUserDeprecationMessage('The tep_guarantee_subarray function has been deprecated.');

        $data = [];
        $subarray = &tep_guarantee_subarray($data, 'legacy');

        $this->assertSame([], $subarray);
        $this->assertArrayHasKey('legacy', $data);
    }
}
