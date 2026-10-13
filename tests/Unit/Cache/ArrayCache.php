<?php

declare(strict_types=1);

namespace Nowo\SepaPaymentBundle\Tests\Unit\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

use function array_key_exists;

/**
 * Simple array-based PSR-16 cache implementation for testing.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class ArrayCache implements CacheInterface
{
    /**
     * Cache storage.
     *
     * @var array<string, mixed>
     */
    private array $storage = [];

    /**
     * Last TTL passed to set().
     */
    public DateInterval|int|null $lastTtl = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->storage) ? $this->storage[$key] : $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->storage[$key] = $value;
        $this->lastTtl       = $ttl;

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

    /**
     * @param iterable<string> $keys
     *
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->storage);
    }
}
