<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Cache;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Service for caching validation results in a PSR-16 cache.
 * Improves performance for repeated validations. Without a cache (the default) it is a no-op;
 * configure `nowo_sepa_payment.validation_cache.service` to enable it.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[AsAlias(id: self::SERVICE_NAME, public: true)]
final class ValidationCache implements ValidationCacheInterface
{
    public const SERVICE_NAME = 'nowo_sepa_payment.cache.validation_cache';

    /**
     * Constructor.
     *
     * @param CacheInterface|null $cache Optional PSR-16 cache (null disables caching)
     * @param int $defaultTtl Default TTL in seconds (default: 3600 = 1 hour)
     */
    public function __construct(
        /**
         * PSR-16 cache (optional).
         */
        private readonly ?CacheInterface $cache = null,
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
        if (!$this->cache instanceof CacheInterface) {
            return null;
        }

        // false is a valid cached value; null means "not cached"
        $result = $this->cache->get($this->normalizeKey($key));

        return $result === null ? null : (bool) $result;
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
        if (!$this->cache instanceof CacheInterface) {
            return;
        }

        // @igor-ignore - Validation results stored in injected PSR-16 cache, not in-process arrays.
        $this->cache->set($this->normalizeKey($key), $value, $ttl ?? $this->defaultTtl);
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
        if (!$this->cache instanceof CacheInterface) {
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
        $this->cache?->delete($this->normalizeKey($key));
    }

    /**
     * Clears all cached validation results.
     *
     * Note: PSR-16 has no namespaced clear, so this clears the whole configured pool;
     * use a dedicated pool for validation results.
     */
    public function clear(): void
    {
        // @igor-ignore - Validation results stored in injected PSR-16 cache, not in-process arrays.
        $this->cache?->clear();
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
