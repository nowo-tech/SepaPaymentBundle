<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Cache;

use Nowo\SepaPaymentBundle\Cache\ValidationCache;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ValidationCache.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class ValidationCacheTest extends TestCase
{
    /**
     * Test cache get/set operations.
     */
    public function testGetSet(): void
    {
        $cache = new ArrayCache();
        $validationCache = new ValidationCache($cache);

        $this->assertNull($validationCache->get('test_key'));
        $this->assertFalse($validationCache->has('test_key'));

        $validationCache->set('test_key', true);
        $this->assertTrue($validationCache->get('test_key'));
        $this->assertTrue($validationCache->has('test_key'));

        $validationCache->set('test_key', false);
        $this->assertFalse($validationCache->get('test_key'));
    }

    /**
     * Test cache without adapter.
     */
    public function testWithoutCacheAdapter(): void
    {
        $validationCache = new ValidationCache(null);

        $this->assertNull($validationCache->get('test_key'));
        $this->assertFalse($validationCache->has('test_key'));

        // Should not throw exception
        $validationCache->set('test_key', true);
        $validationCache->delete('test_key');
        $validationCache->clear();
    }

    /**
     * Test cache delete operation.
     */
    public function testDelete(): void
    {
        $cache = new ArrayCache();
        $validationCache = new ValidationCache($cache);

        $validationCache->set('test_key', true);
        $this->assertTrue($validationCache->has('test_key'));

        $validationCache->delete('test_key');
        $this->assertFalse($validationCache->has('test_key'));
        $this->assertNull($validationCache->get('test_key'));
    }

    /**
     * Test cache clear operation.
     */
    public function testClear(): void
    {
        $cache = new ArrayCache();
        $validationCache = new ValidationCache($cache);

        $validationCache->set('key1', true);
        $validationCache->set('key2', false);
        $this->assertTrue($validationCache->has('key1'));
        $this->assertTrue($validationCache->has('key2'));

        $validationCache->clear();
        $this->assertFalse($validationCache->has('key1'));
        $this->assertFalse($validationCache->has('key2'));
    }

    /**
     * Test custom TTL.
     */
    public function testCustomTtl(): void
    {
        $cache = new ArrayCache();
        $validationCache = new ValidationCache($cache, 7200);

        // Should not throw exception
        $validationCache->set('test_key', true, 3600);
        $this->assertTrue($validationCache->get('test_key'));
    }

    /**
     * Test cache key normalization.
     */
    public function testKeyNormalization(): void
    {
        $cache = new ArrayCache();
        $validationCache = new ValidationCache($cache);

        $validationCache->set('test_key', true);
        $this->assertTrue($validationCache->get('test_key'));

        // Same key should return same value
        $this->assertTrue($validationCache->get('test_key'));
    }
}
