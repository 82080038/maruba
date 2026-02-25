<?php
namespace App\Caching;

use App\Middleware\TenantMiddleware;

/**
 * Multi-Level Caching Strategy for KSP SaaS Platform
 *
 * Implements intelligent caching with tenant isolation:
 * - Application-level caching (APC/APCu)
 * - Database query result caching
 * - File-based caching for static data
 * - Redis support for distributed caching
 * - Tenant-aware cache keys
 */
class CacheManager
{
    private string $cacheDir;
    private int $defaultTtl = 3600; // 1 hour
    private array $cacheLayers = [];

    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../../cache';

        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        // Initialize cache layers
        $this->initializeCacheLayers();
    }

    /**
     * Initialize available cache layers
     */
    private function initializeCacheLayers(): void
    {
        // Layer 1: APC/APCu (fastest, shared memory)
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            $this->cacheLayers['apcu'] = new ApcuCacheLayer();
        }

        // Layer 2: Redis (distributed, persistent)
        if (extension_loaded('redis') && $this->isRedisAvailable()) {
            $this->cacheLayers['redis'] = new RedisCacheLayer();
        }

        // Layer 3: File-based (fallback, persistent)
        $this->cacheLayers['file'] = new FileCacheLayer($this->cacheDir);
    }

    /**
     * Check if Redis is available
     */
    private function isRedisAvailable(): bool
    {
        try {
            $redis = new \Redis();
            $redis->connect($_ENV['REDIS_HOST'] ?? 'localhost', (int)($_ENV['REDIS_PORT'] ?? 6379));
            $redis->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get cache key with tenant context
     */
    private function getTenantCacheKey(string $key): string
    {
        $tenantId = TenantMiddleware::hasTenant() ?
            TenantMiddleware::getTenantSlug() : 'system';

        return "tenant:{$tenantId}:{$key}";
    }

    /**
     * Get data from cache
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->getTenantCacheKey($key);

        // Try each cache layer in order of speed
        foreach ($this->cacheLayers as $layerName => $layer) {
            try {
                $data = $layer->get($cacheKey);
                if ($data !== null) {
                    // Update access time for LRU policies
                    $this->updateCacheAccess($cacheKey, $layerName);
                    return $data;
                }
            } catch (\Exception $e) {
                // Log cache layer failure but continue
                error_log("Cache layer {$layerName} failed: " . $e->getMessage());
            }
        }

        return $default;
    }

    /**
     * Store data in cache
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        $cacheKey = $this->getTenantCacheKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        $success = false;

        // Store in all available cache layers
        foreach ($this->cacheLayers as $layer) {
            try {
                if ($layer->set($cacheKey, $value, $ttl)) {
                    $success = true;
                }
            } catch (\Exception $e) {
                // Log but don't fail completely
                error_log("Cache set failed: " . $e->getMessage());
            }
        }

        return $success;
    }

    /**
     * Delete from cache
     */
    public function delete(string $key): bool
    {
        $cacheKey = $this->getTenantCacheKey($key);

        $success = false;

        // Delete from all cache layers
        foreach ($this->cacheLayers as $layer) {
            try {
                if ($layer->delete($cacheKey)) {
                    $success = true;
                }
            } catch (\Exception $e) {
                // Log but don't fail completely
            }
        }

        return $success;
    }

    /**
     * Clear all cache for current tenant
     */
    public function clearTenantCache(): bool
    {
        $tenantId = TenantMiddleware::hasTenant() ?
            TenantMiddleware::getTenantSlug() : 'system';

        $pattern = "tenant:{$tenantId}:*";

        $success = false;

        // Clear from all cache layers
        foreach ($this->cacheLayers as $layer) {
            try {
                if ($layer->clearPattern($pattern)) {
                    $success = true;
                }
            } catch (\Exception $e) {
                // Log but don't fail completely
            }
        }

        return $success;
    }

    /**
     * Clear all system cache
     */
    public function clearSystemCache(): bool
    {
        $success = false;

        foreach ($this->cacheLayers as $layer) {
            try {
                if ($layer->clearAll()) {
                    $success = true;
                }
            } catch (\Exception $e) {
                // Log but don't fail completely
            }
        }

        return $success;
    }

    /**
     * Get or set cache with callback
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Cache database query results
     */
    public function rememberQuery(string $queryKey, string $sql, array $params = [], int $ttl = null)
    {
        $cacheKey = "query:" . md5($queryKey . serialize($params));
        $ttl = $ttl ?? $this->defaultTtl;

        return $this->remember($cacheKey, $ttl, function() use ($sql, $params) {
            $stmt = \App\Database::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        });
    }

    /**
     * Cache expensive calculations
     */
    public function rememberCalculation(string $key, callable $calculation, int $ttl = null)
    {
        $cacheKey = "calc:{$key}";
        $ttl = $ttl ?? ($this->defaultTtl * 4); // Longer TTL for calculations

        return $this->remember($cacheKey, $ttl, $calculation);
    }

    /**
     * Update cache access time
     */
    private function updateCacheAccess(string $key, string $layer): void
    {
        // For file-based cache, update access time
        if ($layer === 'file') {
            $this->cacheLayers['file']->updateAccessTime($key);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'layers' => [],
            'tenant_cache_size' => 0,
            'hit_rate' => 0,
            'total_keys' => 0
        ];

        foreach ($this->cacheLayers as $layerName => $layer) {
            try {
                $layerStats = $layer->getStats();
                $stats['layers'][$layerName] = $layerStats;
                $stats['total_keys'] += $layerStats['keys_count'] ?? 0;
            } catch (\Exception $e) {
                $stats['layers'][$layerName] = ['error' => $e->getMessage()];
            }
        }

        return $stats;
    }

    /**
     * Warm up frequently accessed data
     */
    public function warmupCache(): void
    {
        // Cache frequently accessed system data
        $this->set('system:subscription_plans', $this->getSubscriptionPlans(), 3600);
        $this->set('system:active_tenants', $this->getActiveTenants(), 1800);

        // Cache tenant-specific data if tenant context exists
        if (TenantMiddleware::hasTenant()) {
            $this->warmupTenantCache();
        }
    }

    /**
     * Warm up tenant-specific cache
     */
    private function warmupTenantCache(): void
    {
        // This would cache frequently accessed tenant data
        // Implementation depends on specific tenant data patterns
    }

    /**
     * Get subscription plans (placeholder)
     */
    private function getSubscriptionPlans(): array
    {
        // In real implementation, fetch from database
        return [
            ['id' => 1, 'name' => 'starter', 'price' => 500000],
            ['id' => 2, 'name' => 'professional', 'price' => 1500000],
            ['id' => 3, 'name' => 'enterprise', 'price' => 3000000]
        ];
    }

    /**
     * Get active tenants (placeholder)
     */
    private function getActiveTenants(): array
    {
        // In real implementation, fetch from database
        return [
            ['id' => 1, 'name' => 'Koperasi ABC', 'slug' => 'koperasi-abc'],
            ['id' => 2, 'name' => 'Koperasi XYZ', 'slug' => 'koperasi-xyz']
        ];
    }

    /**
     * Invalidate cache for specific entities
     */
    public function invalidateEntityCache(string $entityType, int $entityId): void
    {
        $patterns = [
            "{$entityType}:{$entityId}",
            "{$entityType}_list",
            "{$entityType}_stats"
        ];

        foreach ($patterns as $pattern) {
            $this->delete($pattern);
        }
    }

    /**
     * Cache with tags for bulk invalidation
     */
    public function tagCache(string $key, array $tags, $value, int $ttl = null): bool
    {
        // Store the value
        $success = $this->set($key, $value, $ttl);

        if ($success) {
            // Store tag relationships
            foreach ($tags as $tag) {
                $tagKey = $this->getTagKey($tag);
                $taggedKeys = $this->get($tagKey, []);
                $taggedKeys[] = $key;
                $taggedKeys = array_unique($taggedKeys);
                $this->set($tagKey, $taggedKeys, $ttl ?? $this->defaultTtl * 24); // Tags live longer
            }
        }

        return $success;
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $tagKey = $this->getTagKey($tag);
            $taggedKeys = $this->get($tagKey, []);

            foreach ($taggedKeys as $key) {
                $this->delete($key);
            }

            $this->delete($tagKey);
        }
    }

    /**
     * Get tag cache key
     */
    private function getTagKey(string $tag): string
    {
        return "tag:" . $this->getTenantCacheKey($tag);
    }

    /**
     * Get tenant cache key (private version)
     */
    private function getTenantCacheKeyPrivate(string $key): string
    {
        $tenantId = $this->getCurrentTenantId();
        return $tenantId ? "tenant_{$tenantId}:{$key}" : $key;
    }
}

/**
 * APC/APCu Cache Layer
 */
class ApcuCacheLayer
{
    public function get(string $key)
    {
        return apcu_fetch($key, $success) ? $success : null;
    }

    public function set(string $key, $value, int $ttl): bool
    {
        return apcu_store($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return apcu_delete($key);
    }

    public function clearPattern(string $pattern): bool
    {
        // APCu doesn't support pattern clearing, return false
        return false;
    }

    public function clearAll(): bool
    {
        return apcu_clear_cache();
    }

    public function getStats(): array
    {
        $info = apcu_cache_info();
        return [
            'keys_count' => $info['num_entries'] ?? 0,
            'memory_used' => $info['mem_size'] ?? 0,
            'hits' => $info['num_hits'] ?? 0,
            'misses' => $info['num_misses'] ?? 0
        ];
    }
}

/**
 * Redis Cache Layer
 */
class RedisCacheLayer
{
    private \Redis $redis;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect(
            $_ENV['REDIS_HOST'] ?? 'localhost',
            (int)($_ENV['REDIS_PORT'] ?? 6379)
        );

        if (isset($_ENV['REDIS_PASSWORD'])) {
            $this->redis->auth($_ENV['REDIS_PASSWORD']);
        }
    }

    public function get(string $key)
    {
        $data = $this->redis->get($key);
        return $data ? unserialize($data) : null;
    }

    public function set(string $key, $value, int $ttl): bool
    {
        return $this->redis->setex($key, $ttl, serialize($value));
    }

    public function delete(string $key): bool
    {
        return $this->redis->del($key) > 0;
    }

    public function clearPattern(string $pattern): bool
    {
        $keys = $this->redis->keys($pattern);
        if (!empty($keys)) {
            return $this->redis->del($keys) > 0;
        }
        return true;
    }

    public function clearAll(): bool
    {
        return $this->redis->flushdb();
    }

    public function getStats(): array
    {
        $info = $this->redis->info();
        return [
            'keys_count' => $this->redis->dbSize(),
            'memory_used' => $info['used_memory'] ?? 0,
            'hits' => $info['keyspace_hits'] ?? 0,
            'misses' => $info['keyspace_misses'] ?? 0
        ];
    }
}

/**
 * File-based Cache Layer
 */
class FileCacheLayer
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function get(string $key)
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        // Check if expired
        if ($this->isExpired($file)) {
            unlink($file);
            return null;
        }

        $data = file_get_contents($file);
        return $data ? unserialize($data) : null;
    }

    public function set(string $key, $value, int $ttl): bool
    {
        $file = $this->getCacheFile($key);
        $data = serialize($value);

        // Write expiry time as first 10 bytes
        $expiry = time() + $ttl;
        $content = sprintf('%010d', $expiry) . $data;

        return file_put_contents($file, $content, LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    public function clearPattern(string $pattern): bool
    {
        // Convert pattern to regex for file matching
        $regex = str_replace(['*', '?'], ['.*', '.'], $pattern);
        $files = glob($this->cacheDir . '/*');

        $deleted = 0;
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match("/^{$regex}$/", $filename)) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted > 0;
    }

    public function clearAll(): bool
    {
        $files = glob($this->cacheDir . '/*');
        $deleted = 0;

        foreach ($files as $file) {
            if (is_file($file) && unlink($file)) {
                $deleted++;
            }
        }

        return $deleted > 0;
    }

    public function updateAccessTime(string $key): void
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            touch($file);
        }
    }

    public function getStats(): array
    {
        $files = glob($this->cacheDir . '/*');
        $totalSize = 0;
        $validFiles = 0;
        $expiredFiles = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                if ($this->isExpired($file)) {
                    $expiredFiles++;
                } else {
                    $validFiles++;
                }
            }
        }

        return [
            'keys_count' => $validFiles,
            'expired_count' => $expiredFiles,
            'total_size' => $totalSize,
            'hits' => 0, // File cache doesn't track hits
            'misses' => 0
        ];
    }

    private function getCacheFile(string $key): string
    {
        // Sanitize key for filename
        $safeKey = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $key);
        return $this->cacheDir . '/' . $safeKey . '.cache';
    }

    private function isExpired(string $file): bool
    {
        if (!file_exists($file)) {
            return true;
        }

        $handle = fopen($file, 'r');
        $expiry = (int)fread($handle, 10);
        fclose($handle);

        return time() > $expiry;
    }
}

/**
 * Cache Utilities and Helpers
 */
class CacheUtils
{
    private CacheManager $cache;

    public function __construct()
    {
        $this->cache = new CacheManager();
    }

    /**
     * Cache frequently accessed tenant data
     */
    public function cacheTenantData(int $tenantId): void
    {
        // Cache tenant info
        $this->cache->remember("tenant:{$tenantId}:info", 3600, function() use ($tenantId) {
            $tenantModel = new \App\Models\Tenant();
            return $tenantModel->find($tenantId);
        });

        // Cache tenant members count
        $this->cache->remember("tenant:{$tenantId}:members_count", 1800, function() use ($tenantId) {
            // This would query tenant database
            return 0; // Placeholder
        });

        // Cache tenant loans count
        $this->cache->remember("tenant:{$tenantId}:loans_count", 1800, function() use ($tenantId) {
            // This would query tenant database
            return 0; // Placeholder
        });
    }

    /**
     * Invalidate tenant cache on data changes
     */
    public function invalidateTenantData(int $tenantId, string $dataType): void
    {
        $patterns = [
            "tenant:{$tenantId}:{$dataType}",
            "tenant:{$tenantId}:{$dataType}_*"
        ];

        foreach ($patterns as $pattern) {
            $this->cache->delete($pattern);
        }
    }

    /**
     * Cache system-wide data
     */
    public function cacheSystemData(): void
    {
        // Cache all tenants list
        $this->cache->remember('system:tenants_list', 1800, function() {
            $tenantModel = new \App\Models\Tenant();
            return $tenantModel->findWhere(['status' => 'active']);
        });

        // Cache subscription plans
        $this->cache->remember('system:subscription_plans', 3600, function() {
            $planModel = new \App\Models\SubscriptionPlan();
            return $planModel->all();
        });
    }

    /**
     * Get cache manager instance
     */
    public function getCacheManager(): CacheManager
    {
        return $this->cache;
    }
}

// =========================================
// CACHE INTEGRATION POINTS
// =========================================

/*
Integration points for caching in the application:

1. Model Layer Caching:
   - Cache frequently accessed records
   - Cache query results
   - Invalidate on updates

2. Controller Layer Caching:
   - Cache API responses
   - Cache dashboard data
   - Cache reports

3. Service Layer Caching:
   - Cache expensive calculations
   - Cache external API calls
   - Cache file processing results

4. Cache Invalidation Strategy:
   - Time-based expiration
   - Event-based invalidation
   - Tag-based invalidation

Example usage:

// In controllers
$cache = new CacheManager();
$data = $cache->remember('dashboard_data', 1800, function() {
    return $this->getDashboardData();
});

// In models
$cache->tagCache('user:123', ['users', 'active_users'], $userData, 3600);

// Invalidation
$cache->invalidateTags(['users']); // Clears all user-related cache
$cache->invalidateEntityCache('user', 123); // Clears specific user cache
*/

?>
