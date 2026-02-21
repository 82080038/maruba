<?php
/**
 * Cache Clearing Utility
 * Provides methods to clear various types of caches used in the application
 */

class CacheUtil
{
    /**
     * Clear all available caches
     */
    public static function clearAll(): void
    {
        self::clearOPcache();
        self::clearAPCu();
        self::clearFileCache();
        self::clearBrowserCache();

        error_log('All caches cleared via CacheUtil');
    }

    /**
     * Clear PHP OPcache
     */
    public static function clearOPcache(): bool
    {
        if (function_exists('opcache_reset')) {
            $result = opcache_reset();
            error_log('OPcache cleared: ' . ($result ? 'success' : 'failed'));
            return $result;
        }
        return false;
    }

    /**
     * Clear APCu cache
     */
    public static function clearAPCu(): bool
    {
        if (function_exists('apcu_clear_cache')) {
            $result = apcu_clear_cache();
            error_log('APCu cache cleared: ' . ($result ? 'success' : 'failed'));
            return $result;
        }
        return false;
    }

    /**
     * Clear file-based cache
     */
    public static function clearFileCache(): bool
    {
        $cache_dirs = [
            __DIR__ . '/../cache',
            __DIR__ . '/../../cache'
        ];

        $cleared = false;
        foreach ($cache_dirs as $cache_dir) {
            if (is_dir($cache_dir)) {
                $files = glob($cache_dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                        $cleared = true;
                    }
                }
            }
        }

        if ($cleared) {
            error_log('File cache cleared');
        }
        return $cleared;
    }

    /**
     * Send browser cache clearing headers
     */
    public static function clearBrowserCache(): void
    {
        // Prevent browser caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        error_log('Browser cache headers sent');
    }

    /**
     * Clear session data (except current user)
     */
    public static function clearSessionCache(): void
    {
        // Clear all session variables except user data
        $user_backup = $_SESSION['user'] ?? null;

        $_SESSION = array();

        if ($user_backup) {
            $_SESSION['user'] = $user_backup;
        }

        error_log('Session cache cleared (user data preserved)');
    }

    /**
     * Get cache status information
     */
    public static function getStatus(): array
    {
        return [
            'opcache_enabled' => function_exists('opcache_reset'),
            'apcu_enabled' => function_exists('apcu_clear_cache'),
            'file_cache_dir' => is_dir(__DIR__ . '/../cache') ? __DIR__ . '/../cache' : null,
            'session_active' => session_status() === PHP_SESSION_ACTIVE,
            'session_id' => session_id()
        ];
    }
}
?>
