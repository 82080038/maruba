<?php
namespace App\Models;

class CooperativeOnboarding extends Model
{
    protected string $table = 'cooperative_onboardings';
    protected array $fillable = [
        'registration_id', 'tenant_id', 'admin_username', 'admin_password',
        'setup_completed', 'welcome_email_sent', 'initial_config_done',
        'onboarding_steps', 'completed_at', 'notes'
    ];
    protected array $casts = [
        'registration_id' => 'int',
        'tenant_id' => 'int',
        'setup_completed' => 'bool',
        'welcome_email_sent' => 'bool',
        'initial_config_done' => 'bool',
        'onboarding_steps' => 'array',
        'completed_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    /**
     * Create onboarding record for approved cooperative
     */
    public function createOnboarding(int $registrationId, int $tenantId, array $registration): int
    {
        // Generate admin credentials
        $adminUsername = $this->generateAdminUsername($registration['slug']);
        $adminPassword = $this->generateSecurePassword();

        $onboardingSteps = [
            'admin_user_created' => false,
            'welcome_email_sent' => false,
            'initial_products_setup' => false,
            'initial_users_setup' => false,
            'welcome_message_sent' => false
        ];

        return $this->create([
            'registration_id' => $registrationId,
            'tenant_id' => $tenantId,
            'admin_username' => $adminUsername,
            'admin_password' => password_hash($adminPassword, PASSWORD_DEFAULT),
            'setup_completed' => false,
            'welcome_email_sent' => false,
            'initial_config_done' => false,
            'onboarding_steps' => json_encode($onboardingSteps),
            'notes' => 'Onboarding initiated for ' . $registration['cooperative_name']
        ]);
    }

    /**
     * Execute onboarding process
     */
    public function executeOnboarding(int $onboardingId): bool
    {
        $onboarding = $this->find($onboardingId);
        if (!$onboarding) {
            return false;
        }

        $registrationModel = new CooperativeRegistration();
        $registration = $registrationModel->find($onboarding['registration_id']);

        if (!$registration) {
            return false;
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($onboarding['tenant_id']);

        if (!$tenant) {
            return false;
        }

        // Start onboarding process
        try {
            $this->createAdminUser($tenant, $onboarding);
            $this->setupInitialProducts($tenant);
            $this->setupInitialUsers($tenant, $registration);
            $this->setupNavigationMenu($tenant['id']); // Add navigation setup
            $this->sendWelcomeEmail($registration, $onboarding);
            $this->sendWelcomeMessage($registration);

            // Mark onboarding as completed
            $onboardingSteps = json_decode($onboarding['onboarding_steps'], true);
            $onboardingSteps['admin_user_created'] = true;
            $onboardingSteps['welcome_email_sent'] = true;
            $onboardingSteps['initial_products_setup'] = true;
            $onboardingSteps['initial_users_setup'] = true;
            $onboardingSteps['navigation_menu_setup'] = true; // Add navigation step
            $onboardingSteps['welcome_message_sent'] = true;

            $this->update($onboardingId, [
                'setup_completed' => true,
                'welcome_email_sent' => true,
                'initial_config_done' => true,
                'onboarding_steps' => json_encode($onboardingSteps),
                'completed_at' => date('Y-m-d H:i:s'),
                'notes' => 'Onboarding completed successfully for ' . $registration['cooperative_name']
            ]);

            return true;

        } catch (\Exception $e) {
            // Log error and mark onboarding as failed
            $this->update($onboardingId, [
                'notes' => 'Onboarding failed: ' . $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create admin user for the cooperative
     */
    private function createAdminUser(array $tenant, array $onboarding): void
    {
        $tenantDb = $this->getTenantDatabase($tenant['slug']);

        // Create roles table if not exists
        $tenantDb->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                permissions JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insert default roles
        $tenantDb->exec("
            INSERT IGNORE INTO roles (name, permissions) VALUES
            ('admin', '[\"*\"]'),
            ('kasir', '[\"loans.view\",\"loans.create\",\"repayments.view\",\"repayments.create\",\"members.view\"]'),
            ('surveyor', '[\"loans.view\",\"surveys.view\",\"surveys.create\",\"members.view\"]'),
            ('collector', '[\"loans.view\",\"repayments.view\",\"repayments.create\",\"members.view\"]')
        ");

        // Create users table if not exists
        $tenantDb->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role_id INT NOT NULL,
                status ENUM('active','inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (role_id) REFERENCES roles(id)
            )
        ");

        // Create admin user
        $stmt = $tenantDb->prepare("
            INSERT INTO users (name, username, password_hash, role_id)
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([
            $tenant['name'] . ' Admin',
            $onboarding['admin_username'],
            $onboarding['admin_password']
        ]);
    }

    /**
     * Setup initial products for the cooperative
     */
    private function setupInitialProducts(array $tenant): void
    {
        $tenantDb = $this->getTenantDatabase($tenant['slug']);

        // Create products table if not exists
        $tenantDb->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                type ENUM('loan','savings') DEFAULT 'loan',
                rate DECIMAL(5,2) DEFAULT 0,
                tenor_months INT DEFAULT 0,
                fee DECIMAL(12,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insert default products
        $tenantDb->exec("
            INSERT IGNORE INTO products (name, type, rate, tenor_months, fee) VALUES
            ('Simpanan Pokok', 'savings', 0.00, 0, 50000),
            ('Simpanan Wajib', 'savings', 0.00, 0, 0),
            ('Simpanan Sukarela', 'savings', 3.00, 0, 0),
            ('SISUKA', 'savings', 6.00, 0, 0),
            ('Pinjaman Produktif', 'loan', 1.50, 12, 50000),
            ('Pinjaman Konsumtif', 'loan', 1.80, 24, 75000),
            ('Pinjaman Darurat', 'loan', 2.50, 6, 25000)
        ");
    }

    /**
     * Setup initial users/roles for the cooperative
     */
    private function setupInitialUsers(array $tenant, array $registration): void
    {
        $tenantDb = $this->getTenantDatabase($tenant['slug']);

        // Create additional users based on registration data
        if (!empty($registration['chairman_email'])) {
            $this->createUserIfNotExists($tenantDb, [
                'name' => $registration['chairman_name'],
                'username' => 'ketua_' . $tenant['slug'],
                'password' => $this->generateSecurePassword(),
                'role_id' => 1, // admin
                'email' => $registration['chairman_email']
            ]);
        }

        if (!empty($registration['manager_email'])) {
            $this->createUserIfNotExists($tenantDb, [
                'name' => $registration['manager_name'],
                'username' => 'manajer_' . $tenant['slug'],
                'password' => $this->generateSecurePassword(),
                'role_id' => 1, // admin
                'email' => $registration['manager_email']
            ]);
        }
    }

    /**
     * Create user if not exists
     */
    private function createUserIfNotExists(\PDO $db, array $userData): void
    {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$userData['username']]);

        if (!$stmt->fetch()) {
            $stmt = $db->prepare("
                INSERT INTO users (name, username, password_hash, role_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $userData['name'],
                $userData['username'],
                password_hash($userData['password'], PASSWORD_DEFAULT),
                $userData['role_id']
            ]);
        }
    }

    /**
     * Send welcome email to cooperative
     */
    private function sendWelcomeEmail(array $registration, array $onboarding): void
    {
        $welcomeMessage = "
        Selamat datang di " . APP_NAME . "!

        Koperasi {$registration['cooperative_name']} telah berhasil didaftarkan dan diaktifkan.

        Akses Sistem:
        - URL: https://{$registration['slug']}." . $_SERVER['HTTP_HOST'] . "
        - Username Admin: {$onboarding['admin_username']}
        - Password: " . $this->getPlainPassword($onboarding) . "

        Panduan Penggunaan:
        1. Login dengan kredensial di atas
        2. Ubah password default untuk keamanan
        3. Lengkapi profil koperasi
        4. Mulai menambahkan anggota dan produk

        Dukungan teknis: support@" . APP_NAME . ".id

        Selamat menggunakan sistem!
        ";

        \App\Helpers\Notification::send(
            'email',
            [
                'email' => $registration['email'],
                'name' => $registration['chairman_name']
            ],
            'Selamat Datang di ' . APP_NAME,
            $welcomeMessage
        );
    }

    /**
     * Send welcome WhatsApp message
     */
    private function sendWelcomeMessage(array $registration): void
    {
        if (!empty($registration['phone'])) {
            $message = "Selamat! Koperasi {$registration['cooperative_name']} telah berhasil terdaftar di " . APP_NAME . ". Sistem akan segera diaktifkan.";

            \App\Helpers\Notification::send(
                'whatsapp',
                [
                    'phone' => $registration['phone'],
                    'name' => $registration['chairman_name']
                ],
                'Pendaftaran Berhasil',
                $message
            );
        }
    }

    /**
     * Get tenant database connection
     */
    private function getTenantDatabase(string $slug): \PDO
    {
        $tenantDbName = "tenant_{$slug}";
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host=$host;port=$port;dbname=$tenantDbName;charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new \PDO($dsn, $user, $pass, $options);
    }

    /**
     * Generate admin username
     */
    private function generateAdminUsername(string $slug): string
    {
        return 'admin_' . $slug;
    }

    /**
     * Generate secure password
     */
    private function generateSecurePassword(): string
    {
        return bin2hex(random_bytes(8)); // 16 character password
    }

    /**
     * Get plain password (for email only)
     */
    private function getPlainPassword(array $onboarding): string
    {
        // This is a security risk - in production, passwords should be reset by users
        // For demo purposes only
        return 'ChangeMe123!'; // Default password that users must change
    }

    /**
     * Get onboarding by tenant ID
     */
    public function getByTenantId(int $tenantId): ?array
    {
        $onboardings = $this->findWhere(['tenant_id' => $tenantId]);
        return !empty($onboardings) ? $onboardings[0] : null;
    }

    /**
     * Get onboarding statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_onboardings,
                COUNT(CASE WHEN setup_completed = 1 THEN 1 END) as completed_onboardings,
                COUNT(CASE WHEN welcome_email_sent = 1 THEN 1 END) as emails_sent,
                COUNT(CASE WHEN initial_config_done = 1 THEN 1 END) as configs_done
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }
}
