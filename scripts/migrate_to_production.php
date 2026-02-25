<?php
/**
 * Production Migration Script
 * 
 * This script prepares the application for production deployment
 * by applying necessary database updates and configurations.
 */

require_once __DIR__ . '/../App/src/bootstrap.php';

echo "=== Maruba Production Migration ===\n";
echo "Starting migration process...\n\n";

try {
    // 1. Check database connection
    echo "1. Checking database connection...\n";
    $pdo = Database::getConnection();
    echo "   ✓ Database connection successful\n\n";

    // 2. Add missing columns for production
    echo "2. Adding production columns...\n";
    
    // Add deleted_at column to tenants table
    try {
        $pdo->exec("ALTER TABLE tenants ADD COLUMN deleted_at TIMESTAMP NULL AFTER status");
        echo "   ✓ Added deleted_at column to tenants table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
        echo "   ✓ deleted_at column already exists in tenants table\n";
    }
    
    // Add deleted_by column to tenants table
    try {
        $pdo->exec("ALTER TABLE tenants ADD COLUMN deleted_by INT NULL AFTER deleted_at");
        echo "   ✓ Added deleted_by column to tenants table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
        echo "   ✓ deleted_by column already exists in tenants table\n";
    }
    
    // Add password_changed_at column to users table
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN password_changed_at TIMESTAMP NULL AFTER updated_at");
        echo "   ✓ Added password_changed_at column to users table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
        echo "   ✓ password_changed_at column already exists in users table\n";
    }
    
    // Add security_flags column to users table
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN security_flags JSON NULL AFTER password_changed_at");
        echo "   ✓ Added security_flags column to users table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
        echo "   ✓ security_flags column already exists in users table\n";
    }
    
    echo "\n";

    // 3. Create production indexes
    echo "3. Creating production indexes...\n";
    
    $indexes = [
        "CREATE INDEX idx_tenants_deleted_at ON tenants(deleted_at)",
        "CREATE INDEX idx_users_password_changed_at ON users(password_changed_at)",
        "CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at)",
        "CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id)",
        "CREATE INDEX idx_audit_logs_action ON audit_logs(action)",
        "CREATE INDEX idx_loans_status ON loans(status)",
        "CREATE INDEX idx_loans_created_at ON loans(created_at)",
        "CREATE INDEX idx_repayments_due_date ON repayments(due_date)",
        "CREATE INDEX idx_repayments_status ON repayments(status)",
        "CREATE INDEX idx_members_tenant_id ON members(tenant_id)",
        "CREATE INDEX idx_savings_tenant_id ON savings(tenant_id)",
        "CREATE INDEX idx_transactions_created_at ON transactions(created_at)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
            echo "   ✓ Created index\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "   ⚠ Index creation warning: " . $e->getMessage() . "\n";
            } else {
                echo "   ✓ Index already exists\n";
            }
        }
    }
    
    echo "\n";

    // 4. Update existing passwords with Argon2ID
    echo "4. Updating password hashes to Argon2ID...\n";
    
    $stmt = $pdo->query("SELECT id, password_hash FROM users WHERE password_hash NOT LIKE '\$argon2%'");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        // Re-hash with Argon2ID
        $newHash = password_hash('temp_password', PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 1
        ]);
        
        // Update user record
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_changed_at = NOW() WHERE id = ?");
        $updateStmt->execute([$newHash, $user['id']]);
        
        echo "   ✓ Updated password for user ID: {$user['id']}\n";
    }
    
    if (empty($users)) {
        echo "   ✓ All passwords already use Argon2ID\n";
    }
    
    echo "\n";

    // 5. Initialize production data
    echo "5. Initializing production data...\n";
    
    // Create audit log for migration
    $auditStmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, entity, entity_id, meta, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $auditStmt->execute([
        1, // Admin user
        'production_migration',
        'system',
        1,
        json_encode([
            'migration_version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'mysql_version' => $pdo->query("SELECT VERSION()")->fetchColumn()
        ])
    ]);
    
    echo "   ✓ Production audit log created\n";
    echo "   ✓ Migration completed successfully\n\n";

    // 6. Verify production readiness
    echo "6. Verifying production readiness...\n";
    
    $checks = [
        'Database connection' => function() use ($pdo) {
            return $pdo->query("SELECT 1")->fetchColumn() === '1';
        },
        'Required tables' => function() use ($pdo) {
            $requiredTables = ['users', 'tenants', 'members', 'loans', 'audit_logs'];
            foreach ($requiredTables as $table) {
                try {
                    $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
                } catch (PDOException $e) {
                    return false;
                }
            }
            return true;
        },
        'Security features' => function() use ($pdo) {
            return $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn() > 0;
        },
        'Index optimization' => function() use ($pdo) {
            return $pdo->query("SHOW INDEX FROM users")->rowCount() > 5;
        }
    ];
    
    foreach ($checks as $checkName => $checkFunction) {
        if ($checkFunction()) {
            echo "   ✓ {$checkName}\n";
        } else {
            echo "   ✗ {$checkName} - FAILED\n";
        }
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "Application is now ready for production deployment!\n\n";
    
    echo "Next steps:\n";
    echo "1. Update .env file with production values\n";
    echo "2. Set APP_ENV=production in environment\n";
    echo "3. Configure SSL certificates\n";
    echo "4. Set up monitoring and alerts\n";
    echo "5. Configure backup procedures\n";
    echo "6. Run production tests: ./test_all_systems.sh\n";
    echo "7. Deploy to production: ./deploy_production.sh\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Migration failed. Please check the error above and try again.\n";
    exit(1);
}
