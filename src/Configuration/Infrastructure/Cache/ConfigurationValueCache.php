<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Cache-aside wrapper used only by GetConfigurationValueHandler. Entirely internal
 * to the Configuration module — no Fetcher in any other module knows this exists.
 *
 * Backed by the "app" cache pool: filesystem-adapter by default (see
 * config/packages/cache.yaml), no extra service required to run. Swapping to Redis
 * for a multi-instance deployment is a config change, not a code change.
 *
 * Needs both cache interfaces: CacheInterface::get() for compute-if-absent reads,
 * AdapterInterface::clear() for the rotation command's pre-key-removal purge — no
 * single Symfony cache interface covers both.
 */
class ConfigurationValueCache
{
    private const string KEY_PREFIX = 'configuration.value.';

    public function __construct(
        private readonly AdapterInterface&CacheInterface $cache
    ) {
    }

    public function get(string $key, callable $compute): mixed
    {
        return $this->cache->get(self::KEY_PREFIX . $key, static fn () => $compute());
    }

    public function invalidate(string $key): void
    {
        $this->cache->delete(self::KEY_PREFIX . $key);
    }

    /**
     * Used by the key rotation command before an outgoing key is removed, so no
     * cache entry still holds a value encrypted under a key that no longer exists.
     */
    public function clear(): void
    {
        $this->cache->clear();
    }
}
