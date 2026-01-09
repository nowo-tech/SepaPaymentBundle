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
     * Cache adapter (optional).
     *
     * @var object|null
     */
    private $cache;

    /**
     * Default cache TTL in seconds.
     *
     * @var int
     */
    private int $defaultTtl;

    /**
     * Constructor.
     *
     * @param object|null $cache      Optional cache adapter (PSR-16 SimpleCache compatible)
     * @param int         $defaultTtl Default TTL in seconds (default: 3600 = 1 hour)
     */
    public function __construct(
        $cache = null,
        int $defaultTtl = 3600
    ) {
        $this->cache = $cache;
        $this->defaultTtl = $defaultTtl;
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
        if (null === $this->cache || !method_exists($this->cache, 'get')) {
            return null;
        }

        $normalizedKey = $this->normalizeKey($key);

        // First check if key exists (for caches that support has() method)
        if (method_exists($this->cache, 'has')) {
            if (!$this->cache->has($normalizedKey)) {
                return null; // Key doesn't exist
            }
        }

        $result = $this->cache->get($normalizedKey);

        // If result is null and we couldn't check existence, assume it doesn't exist
        if (null === $result && !method_exists($this->cache, 'has')) {
            return null;
        }

        // If we have a result (even if it's false), return it as bool
        // Note: false is a valid cached value, null means not cached
        // Since we already checked has(), if result is null here, it means the key exists but value is null
        // This shouldn't happen for bool values, but we'll return null anyway
        if (null === $result) {
            return null;
        }

        return (bool) $result;
    }

    /**
     * Sets a validation result in cache.
     *
     * @param string $key   Cache key
     * @param bool   $value Validation result
     * @param int    $ttl   Time to live in seconds (optional, uses default if not provided)
     *
     * @return void
     */
    public function set(string $key, bool $value, ?int $ttl = null): void
    {
        if (null === $this->cache || !method_exists($this->cache, 'set')) {
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
        if (null === $this->cache || !method_exists($this->cache, 'has')) {
            return false;
        }

        return $this->cache->has($this->normalizeKey($key));
    }

    /**
     * Deletes a cached value.
     *
     * @param string $key Cache key
     *
     * @return void
     */
    public function delete(string $key): void
    {
        if (null === $this->cache || !method_exists($this->cache, 'delete')) {
            return;
        }

        $this->cache->delete($this->normalizeKey($key));
    }

    /**
     * Clears all cached validation results.
     *
     * @return void
     */
    public function clear(): void
    {
        if (null === $this->cache || !method_exists($this->cache, 'clear')) {
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
