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

    /**
     * Test get when cache adapter has no has() method and get returns null.
     * Covers the branch where we return null when result is null and !method_exists($this->cache, 'has').
     */
    public function testGetWithAdapterWithoutHasMethodReturnsNull(): void
    {
        $cacheWithoutHas = new class() {
            private array $storage = [];

            public function get(string $key): mixed
            {
                return $this->storage[$key] ?? null;
            }

            public function set(string $key, mixed $value, ?int $ttl = null): bool
            {
                $this->storage[$key] = $value;

                return true;
            }

            public function delete(string $key): bool
            {
                unset($this->storage[$key]);

                return true;
            }

            public function clear(): bool
            {
                $this->storage = [];

                return true;
            }
        };
        $validationCache = new ValidationCache($cacheWithoutHas);
        $this->assertNull($validationCache->get('missing_key'));

        // When adapter has no has() but get() returns a value, we should return it (covers return (bool) $result)
        $validationCache->set('stored_key', true);
        $this->assertTrue($validationCache->get('stored_key'));
        $validationCache->set('false_key', false);
        $this->assertFalse($validationCache->get('false_key'));
    }

    /**
     * Test get when key exists but get() returns null (edge case: key exists, value null).
     * Covers the branch where result is null after has() returned true.
     */
    public function testGetWhenStoredValueIsNull(): void
    {
        $normalizedKey = 'sepa_validation_' . md5('null_key');
        $cache = new class($normalizedKey) {
            private string $key;

            public function __construct(string $key)
            {
                $this->key = $key;
            }

            public function get(string $key): mixed
            {
                return $key === $this->key ? null : false;
            }

            public function set(string $key, mixed $value, ?int $ttl = null): bool
            {
                return true;
            }

            public function has(string $key): bool
            {
                return $key === $this->key;
            }

            public function delete(string $key): bool
            {
                return true;
            }

            public function clear(): bool
            {
                return true;
            }
        };
        $validationCache = new ValidationCache($cache);
        $this->assertTrue($validationCache->has('null_key'));
        $this->assertNull($validationCache->get('null_key'));
    }
}
