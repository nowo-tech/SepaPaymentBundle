<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Cache;

/**
 * Interface for validation caching services.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
interface ValidationCacheInterface
{
    /**
     * Gets a cached validation result.
     *
     * @param string $key Cache key
     *
     * @return bool|null Cached result (true/false) or null if not cached
     */
    public function get(string $key): ?bool;

    /**
     * Sets a validation result in cache.
     *
     * @param string $key Cache key
     * @param bool $value Validation result
     * @param int $ttl Time to live in seconds (optional, uses default if not provided)
     */
    public function set(string $key, bool $value, ?int $ttl = null): void;

    /**
     * Checks if a key exists in cache.
     *
     * @param string $key Cache key
     *
     * @return bool True if key exists, false otherwise
     */
    public function has(string $key): bool;

    /**
     * Deletes a cached value.
     *
     * @param string $key Cache key
     */
    public function delete(string $key): void;

    /**
     * Clears all cached validation results.
     */
    public function clear(): void;
}
