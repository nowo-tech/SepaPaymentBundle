<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Cache;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Service for caching validation results.
 * Improves performance for repeated validations.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
class ValidationCache implements ValidationCacheInterface
{
    public const SERVICE_NAME = 'nowo_sepa_payment.cache.validation_cache';

    /**
     * Constructor.
     *
     * @param object|null $cache Optional cache adapter (PSR-16 SimpleCache compatible)
     * @param int $defaultTtl Default TTL in seconds (default: 3600 = 1 hour)
     */
    public function __construct(
        /**
         * Cache adapter (optional).
         */
        private $cache = null,
        /**
         * Default cache TTL in seconds.
         */
        private readonly int $defaultTtl = 3600
    ) {
    }

    /**
     * Gets a cached validation result.
     *
     * @param string $key Cache key
     *
     * @return bool|null Cached result (true/false) or null if not cached
     */
    public function get(string $key): ?bool
    {
        if ($this->cache === null || !method_exists($this->cache, 'get')) {
            return null;
        }

        $normalizedKey = $this->normalizeKey($key);

        // First check if key exists (for caches that support has() method)
        if (method_exists($this->cache, 'has') && !$this->cache->has($normalizedKey)) {
            return null;
            // Key doesn't exist
        }

        $result = $this->cache->get($normalizedKey);

        // If result is null and we couldn't check existence, assume it doesn't exist
        if ($result === null && !method_exists($this->cache, 'has')) {
            return null;
        }

        // If we have a result (even if it's false), return it as bool
        // Note: false is a valid cached value, null means not cached
        // Since we already checked has(), if result is null here, it means the key exists but value is null
        // This shouldn't happen for bool values, but we'll return null anyway
        if ($result === null) {
            return null;
        }

        return (bool) $result;
    }

    /**
     * Sets a validation result in cache.
     *
     * @param string $key Cache key
     * @param bool $value Validation result
     * @param int $ttl Time to live in seconds (optional, uses default if not provided)
     */
    public function set(string $key, bool $value, ?int $ttl = null): void
    {
        if ($this->cache === null || !method_exists($this->cache, 'set')) {
            return;
        }

        $ttl ??= $this->defaultTtl;
        $this->cache->set($this->normalizeKey($key), $value, $ttl);
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
        if ($this->cache === null || !method_exists($this->cache, 'has')) {
            return false;
        }

        return $this->cache->has($this->normalizeKey($key));
    }

    /**
     * Deletes a cached value.
     *
     * @param string $key Cache key
     */
    public function delete(string $key): void
    {
        if ($this->cache === null || !method_exists($this->cache, 'delete')) {
            return;
        }

        $this->cache->delete($this->normalizeKey($key));
    }

    /**
     * Clears all cached validation results.
     */
    public function clear(): void
    {
        if ($this->cache === null || !method_exists($this->cache, 'clear')) {
            return;
        }

        $this->cache->clear();
    }

    /**
     * Normalizes a cache key.
     *
     * @param string $key Original key
     *
     * @return string Normalized key with prefix
     */
    private function normalizeKey(string $key): string
    {
        return 'sepa_validation_' . md5($key);
    }
}
