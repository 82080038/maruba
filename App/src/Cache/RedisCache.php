<?php
namespace App\Cache;

/**
 * Redis Cache Implementation
 * Provides Redis-based caching for Maruba application
 */

class RedisCache
{
    private static $instance = null;
    private $redis = null;
    private $connected = false;
    
    private function __construct()
    {
        $this->connect();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect(): void
    {
        try {
            $this->redis = new \Redis();
            $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
            $port = $_ENV['REDIS_PORT'] ?? 6379;
            $password = $_ENV['REDIS_PASSWORD'] ?? null;
            
            $this->redis->connect($host, $port);
            
            if ($password) {
                $this->redis->auth($password);
            }
            
            $this->connected = true;
        } catch (\Exception $e) {
            $this->connected = false;
            error_log("Redis connection failed: " . $e->getMessage());
        }
    }
    
    public function isConnected(): bool
    {
        return $this->connected;
    }
    
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        if (!$this->connected) {
            return false;
        }
        
        try {
            $serialized = serialize($value);
            return $this->redis->setex($key, $ttl, $serialized);
        } catch (\Exception $e) {
            error_log("Redis set failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function get(string $key)
    {
        if (!$this->connected) {
            return null;
        }
        
        try {
            $value = $this->redis->get($key);
            return $value !== null ? unserialize($value) : null;
        } catch (\Exception $e) {
            error_log("Redis get failed: " . $e->getMessage());
            return null;
        }
    }
    
    public function delete(string $key): bool
    {
        if (!$this->connected) {
            return false;
        }
        
        try {
            return $this->redis->del($key) > 0;
        } catch (\Exception $e) {
            error_log("Redis delete failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function exists(string $key): bool
    {
        if (!$this->connected) {
            return false;
        }
        
        try {
            return $this->redis->exists($key);
        } catch (\Exception $e) {
            error_log("Redis exists failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function clear(): bool
    {
        if (!$this->connected) {
            return false;
        }
        
        try {
            return $this->redis->flushDB();
        } catch (\Exception $e) {
            error_log("Redis clear failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function increment(string $key, int $value = 1): int
    {
        if (!$this->connected) {
            return 0;
        }
        
        try {
            return $this->redis->incrBy($key, $value);
        } catch (\Exception $e) {
            error_log("Redis increment failed: " . $e->getMessage());
            return 0;
        }
    }
    
    public function decrement(string $key, int $value = 1): int
    {
        if (!$this->connected) {
            return 0;
        }
        
        try {
            return $this->redis->decrBy($key, $value);
        } catch (\Exception $e) {
            error_log("Redis decrement failed: " . $e->getMessage());
            return 0;
        }
    }
    
    public function setHash(string $key, array $data, int $ttl = 3600): bool
    {
        if (!$this->connected) {
            return false;
        }
        
        try {
            $this->redis->multi();
            foreach ($data as $field => $value) {
                $this->redis->hSet($key, $field, serialize($value));
            }
            $this->redis->exec();
            
            if ($ttl > 0) {
                $this->redis->expire($key, $ttl);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Redis setHash failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getHash(string $key, string $field = null)
    {
        if (!$this->connected) {
            return null;
        }
        
        try {
            if ($field) {
                $value = $this->redis->hGet($key, $field);
                return $value !== false ? unserialize($value) : null;
            } else {
                $data = $this->redis->hGetAll($key);
                return array_map('unserialize', $data);
            }
        } catch (\Exception $e) {
            error_log("Redis getHash failed: " . $e->getMessage());
            return null;
        }
    }
    
    public function deleteHash(string $key, string $field = null): bool
    {
        if (!$this->connected) {
            return false;
        }
        
        try {
            if ($field) {
                return $this->redis->hDel($key, $field) > 0;
            } else {
                return $this->redis->del($key) > 0;
            }
        } catch (\Exception $e) {
            error_log("Redis deleteHash failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getList(string $key): array
    {
        if (!$this->connected) {
            return [];
        }
        
        try {
            $data = $this->redis->lRange($key, 0, -1);
            return array_map('unserialize', $data);
        } catch (\Exception $e) {
            error_log("Redis getList failed: " . $e->getMessage());
            return [];
        }
    }
    
    public function addToList(string $key, $value): bool
    {
        if (!$this->connected) {
            return false;
        }
        
        try {
            return $this->redis->rPush($key, serialize($value)) > 0;
        } catch (\Exception $e) {
            error_log("Redis addToList failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function removeFromList(string $key, $value): bool
    {
        if (!$this->connected) {
            return false;
        }
        
        try {
            return $this->redis->lRem($key, 0, serialize($value)) > 0;
        } catch (\Exception $e) {
            error_log("Redis removeFromList failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStats(): array
    {
        if (!$this->connected) {
            return [];
        }
        
        try {
            $info = $this->redis->info();
            return [
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory' => $info['used_memory_human'] ?? '0B',
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0,
            ];
        } catch (\Exception $e) {
            error_log("Redis getStats failed: " . $e->getMessage());
            return [];
        }
    }
}
