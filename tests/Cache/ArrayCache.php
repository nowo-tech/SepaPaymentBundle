<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Cache;

/**
 * Simple array-based cache implementation for testing.
 * Implements PSR-16 SimpleCache interface methods.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class ArrayCache
{
    /**
     * Cache storage.
     *
     * @var array<string, mixed>
     */
    private array $storage = [];

    /**
     * Gets a value from cache.
     *
     * @param string $key Cache key
     *
     * @return mixed Cached value or null if not found
     */
    public function get(string $key): mixed
    {
        if (!isset($this->storage[$key])) {
            return null;
        }

        return $this->storage[$key];
    }

    /**
     * Sets a value in cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live (ignored in this simple implementation)
     *
     * @return bool True on success
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->storage[$key] = $value;

        return true;
    }

    /**
     * Checks if a key exists in cache.
     *
     * @param string $key Cache key
     *
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    /**
     * Deletes a value from cache.
     *
     * @param string $key Cache key
     *
     * @return bool True on success
     */
    public function delete(string $key): bool
    {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);

            return true;
        }

        return false;
    }

    /**
     * Clears all cache entries.
     *
     * @return bool True on success
     */
    public function clear(): bool
    {
        $this->storage = [];

        return true;
    }
}
