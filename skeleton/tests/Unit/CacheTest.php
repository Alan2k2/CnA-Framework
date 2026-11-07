<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Cache\Stores\ArrayStore;
use CnA\Testing\TestCase;

class CacheTest extends TestCase
{
    public function testArrayCacheOperations(): void
    {
        $store = new ArrayStore();

        $this->assertFalse($store->has('test_key'));
        $this->assertNull($store->get('test_key'));

        $store->set('test_key', 'cached_value', 60);
        $this->assertTrue($store->has('test_key'));
        $this->assertEquals('cached_value', $store->get('test_key'));

        $val = $store->remember('remembered_key', 60, fn() => 'computed_value');
        $this->assertEquals('computed_value', $val);
        $this->assertEquals('computed_value', $store->get('remembered_key'));

        $store->delete('test_key');
        $this->assertFalse($store->has('test_key'));

        $store->clear();
        $this->assertFalse($store->has('remembered_key'));
    }
}
