<?php
namespace App;

use App\Middleware\TenantMiddleware;

class Database
{
    private static ?\PDO $instance = null;
    private static ?\PDO $tenantInstance = null;

    public static function getConnection(): \PDO
    {
        // Check if we're in tenant context
        if (TenantMiddleware::hasTenant()) {
            return self::getTenantConnection();
        }

        // Main application database
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $dbname = $_ENV['DB_NAME'] ?? 'maruba';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? 'root';

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$instance = new \PDO($dsn, $user, $pass, $options);
        }
        return self::$instance;
    }

    /**
     * Get tenant-specific database connection
     */
    public static function getTenantConnection(): \PDO
    {
        if (self::$tenantInstance === null) {
            // Get tenant database from middleware
            $tenantDb = TenantMiddleware::getTenantDb();

            if ($tenantDb) {
                self::$tenantInstance = $tenantDb;
            } else {
                // Fallback to main database if no tenant context
                return self::getConnection();
            }
        }
        return self::$tenantInstance;
    }

    /**
     * Check if we're using tenant database
     */
    public static function isTenantDb(): bool
    {
        return TenantMiddleware::hasTenant() && self::$tenantInstance !== null;
    }

    /**
     * Clear tenant database connection (for testing or context switching)
     */
    public static function clearTenantConnection(): void
    {
        if (self::$tenantInstance) {
            self::$tenantInstance = null;
        }
    }

    /**
     * Execute query on appropriate database
     */
    public static function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        $db = self::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get database info for debugging
     */
    public static function getDbInfo(): array
    {
        return [
            'is_tenant' => self::isTenantDb(),
            'tenant_slug' => TenantMiddleware::getTenantSlug(),
            'main_db' => self::$instance ? 'connected' : 'not connected',
            'tenant_db' => self::$tenantInstance ? 'connected' : 'not connected'
        ];
    }
}
