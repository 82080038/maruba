<?php
// Front controller - SINGLE ENTRY POINT
require_once __DIR__ . '/App/src/bootstrap.php';

// Handle static assets directly (bypass MVC framework)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

if (preg_match('#^/maruba/(assets|App/public/assets)/(.+\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot))$#', $requestUri, $matches)) {
    $assetPath = __DIR__ . '/' . $matches[1] . '/' . $matches[2];

    if (file_exists($assetPath)) {
        // Set correct content type
        $extension = strtolower($matches[3]);
        $contentTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject'
        ];

        if (isset($contentTypes[$extension])) {
            header('Content-Type: ' . $contentTypes[$extension]);
        }

        // Disable caching for development
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Serve the file
        readfile($assetPath);
        exit;
    }
}

// Apply security headers based on environment
use App\Middleware\SecurityMiddleware;
SecurityMiddleware::applySecurityHeaders();

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\LoanController;
use App\Controllers\MembersController;
use App\Controllers\ProductsController;
use App\Controllers\SurveysController;
use App\Controllers\RepaymentsController;
use App\Controllers\ReportsController;
use App\Controllers\UsersController;
use App\Controllers\AuditController;
use App\Controllers\DisbursementController;
use App\Controllers\TenantController;
use App\Controllers\TenantBackupController;
use App\Controllers\TenantCustomizationController;
use App\Controllers\ApiController;
use App\Controllers\SuratController;
use App\Controllers\RegisterController;

$router = new Router();

// Get the request URI and method
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Remove query string from URI
$uri = parse_url($requestUri, PHP_URL_PATH);

// Handle legacy index.php URLs
if (strpos($uri, '/index.php/') !== false) {
    // Extract path after /index.php/
    $uri = substr($uri, strpos($uri, '/index.php/') + strlen('/index.php/'));
} elseif (strpos($uri, '/index.php') !== false) {
    // Just /index.php without trailing slash
    $uri = '';
} else {
    // Remove base path from URI
    $basePath = '/maruba';
    if (strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
}

// Remove leading slash
$uri = ltrim($uri, '/');

// Handle empty URI (root URL)
if ($uri === '') {
    $uri = '/';
}

// Root route - redirect based on authentication status
$router->get('/', function() {
    if (!empty($_SESSION['user'])) {
        // User is logged in, redirect to dashboard
        header('Location: ' . route_url('dashboard'));
        return;
    } else {
        // User is not logged in, show login page
        $controller = new AuthController();
        $controller->showLogin();
        return;
    }
});

// Authentication routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Registration routes
$router->get('/register', [RegisterController::class, 'showRegisterForm']);
$router->post('/register', [RegisterController::class, 'registerUser']);
$router->get('/register/cooperative', [RegisterController::class, 'showCooperativeForm']);
$router->post('/register/cooperative', [RegisterController::class, 'registerCooperative']);
$router->get('/api/register/cooperatives', [RegisterController::class, 'cooperativesJson']);

// Dashboard route
$router->get('/dashboard', [DashboardController::class, 'index']);

// Loan routes
$router->get('/loans', [LoanController::class, 'index']);
$router->get('/loans/create', [LoanController::class, 'create']);
$router->post('/loans/store', [LoanController::class, 'store']);
$router->get('/loans/edit', [LoanController::class, 'edit']);
$router->post('/loans/update', [LoanController::class, 'update']);
$router->post('/loans/delete', [LoanController::class, 'delete']);
$router->get('/loans/show', [LoanController::class, 'show']);

// Member routes
$router->get('/members', [MembersController::class, 'index']);
$router->get('/members/create', [MembersController::class, 'create']);
$router->post('/members/store', [MembersController::class, 'store']);
$router->get('/members/edit', [MembersController::class, 'edit']);
$router->post('/members/update', [MembersController::class, 'update']);
$router->get('/members/show', [MembersController::class, 'show']);
$router->post('/members/delete', [MembersController::class, 'delete']);

// Product routes
$router->get('/products', [ProductsController::class, 'index']);
$router->get('/products/create', [ProductsController::class, 'create']);
$router->post('/products/store', [ProductsController::class, 'store']);
$router->get('/products/edit', [ProductsController::class, 'edit']);
$router->post('/products/update', [ProductsController::class, 'update']);
$router->post('/products/delete', [ProductsController::class, 'delete']);

// Survey routes
$router->get('/surveys', [SurveysController::class, 'index']);
$router->get('/surveys/create', [SurveysController::class, 'create']);
$router->post('/surveys/store', [SurveysController::class, 'store']);
$router->get('/surveys/edit', [SurveysController::class, 'edit']);
$router->post('/surveys/update', [SurveysController::class, 'update']);
$router->post('/surveys/delete', [SurveysController::class, 'delete']);

// Repayment routes
$router->get('/repayments', [RepaymentsController::class, 'index']);
$router->get('/repayments/create', [RepaymentsController::class, 'create']);
$router->post('/repayments/store', [RepaymentsController::class, 'store']);
$router->get('/repayments/edit', [RepaymentsController::class, 'edit']);
$router->post('/repayments/update', [RepaymentsController::class, 'update']);
$router->post('/repayments/delete', [RepaymentsController::class, 'delete']);

// Report routes
$router->get('/reports', [ReportsController::class, 'index']);
$router->get('/reports/export', [ReportsController::class, 'export']);

// User routes
$router->get('/users', [UsersController::class, 'index']);
$router->get('/users/create', [UsersController::class, 'create']);
$router->post('/users/store', [UsersController::class, 'store']);
$router->get('/users/edit', [UsersController::class, 'edit']);
$router->post('/users/update', [UsersController::class, 'update']);
$router->post('/users/delete', [UsersController::class, 'delete']);

// Audit route
$router->get('/audit', [AuditController::class, 'index']);

// Disbursement routes
$router->get('/disbursement', [DisbursementController::class, 'index']);
$router->get('/disbursement/create', [DisbursementController::class, 'create']);
$router->post('/disbursement/store', [DisbursementController::class, 'store']);
$router->get('/disbursement/edit', [DisbursementController::class, 'edit']);
$router->post('/disbursement/update', [DisbursementController::class, 'update']);
$router->post('/disbursement/delete', [DisbursementController::class, 'delete']);

// Surat routes
$router->get('/surat', [SuratController::class, 'index']);
$router->get('/surat/lamaran-kerja', [SuratController::class, 'lamaranKerja']);
$router->get('/surat/permohonan-anggota', [SuratController::class, 'permohonanAnggota']);
$router->get('/surat/daftar-sah', [SuratController::class, 'daftarSah']);
$router->get('/surat/permohonan-pinjaman', [SuratController::class, 'permohonanPinjaman']);
$router->get('/surat/skb', [SuratController::class, 'skb']);

// Tenant Management routes
$router->get('/tenant', [TenantController::class, 'index']);
$router->get('/tenant/create', [TenantController::class, 'create']);
$router->post('/tenant/store', [TenantController::class, 'store']);
$router->get('/tenant/view/{id}', [TenantController::class, 'view']);
$router->get('/tenant/edit/{id}', [TenantController::class, 'edit']);
$router->post('/tenant/update/{id}', [TenantController::class, 'update']);
$router->post('/tenant/delete/{id}', [TenantController::class, 'delete']);
$router->get('/tenant/billing/{id}', [TenantController::class, 'billing']);

// Tenant Backup routes
$router->get('/tenant/backup', [TenantBackupController::class, 'index']);
$router->post('/tenant/backup/create', [TenantBackupController::class, 'create']);
$router->get('/tenant/backup/download/{id}', [TenantBackupController::class, 'download']);
$router->post('/tenant/backup/restore', [TenantBackupController::class, 'restore']);

// Tenant Customization routes
$router->get('/tenant/customize', [TenantCustomizationController::class, 'index']);
$router->post('/tenant/customize/update', [TenantCustomizationController::class, 'update']);
$router->post('/tenant/customize/upload-logo', [TenantCustomizationController::class, 'uploadLogo']);

// API endpoints
$router->get('/api/members', [ApiController::class, 'members']);
$router->get('/api/surveys', [ApiController::class, 'surveys']);
$router->post('/api/members/geo', [ApiController::class, 'updateMemberGeo']);
$router->post('/api/surveys/geo', [ApiController::class, 'updateSurveyGeo']);
$router->get('/api/dashboard', [ApiController::class, 'dashboard']);
$router->get('/api/tenants', [ApiController::class, 'getTenants']);

// Savings routes
$router->get('/savings', [SavingsController::class, 'index']);
$router->get('/savings/create', [SavingsController::class, 'create']);
$router->post('/savings/store', [SavingsController::class, 'store']);
$router->get('/savings/edit', [SavingsController::class, 'edit']);
$router->post('/savings/update', [SavingsController::class, 'update']);
$router->post('/savings/delete', [SavingsController::class, 'delete']);
$router->get('/savings/show', [SavingsController::class, 'show']);

// ===== SHU (SISA HASIL USAHA) SYSTEM =====
use App\Controllers\SHUController;
$router->get('/shu', [SHUController::class, 'index']);
$router->get('/shu/calculate', [SHUController::class, 'calculate']);
$router->post('/shu/calculate', [SHUController::class, 'processCalculation']);
$router->get('/shu/distribute', [SHUController::class, 'distribute']);
$router->post('/shu/distribute', [SHUController::class, 'processDistribution']);

// Accounting routes
$router->get('/accounting', [AccountingController::class, 'index']);
$router->get('/accounting/journal', [AccountingController::class, 'journal']);
$router->get('/accounting/journal/create', [AccountingController::class, 'createJournal']);
$router->post('/accounting/journal/store', [AccountingController::class, 'storeJournal']);
$router->get('/accounting/journal/edit', [AccountingController::class, 'editJournal']);
$router->post('/accounting/journal/update', [AccountingController::class, 'updateJournal']);
$router->post('/accounting/journal/delete', [AccountingController::class, 'deleteJournal']);
$router->get('/accounting/chart', [AccountingController::class, 'chartOfAccounts']);
$router->get('/accounting/chart/create', [AccountingController::class, 'createAccount']);
$router->post('/accounting/chart/store', [AccountingController::class, 'storeAccount']);
$router->get('/accounting/chart/edit', [AccountingController::class, 'editAccount']);
$router->post('/accounting/chart/update', [AccountingController::class, 'updateAccount']);
$router->post('/accounting/chart/delete', [AccountingController::class, 'deleteAccount']);
$router->get('/accounting/reports', [AccountingController::class, 'reports']);
$router->get('/accounting/trial_balance', [AccountingController::class, 'trialBalance']);
$router->get('/accounting/balance_sheet', [AccountingController::class, 'balanceSheet']);
$router->get('/accounting/income_statement', [AccountingController::class, 'incomeStatement']);
$router->get('/accounting/cash_flow', [AccountingController::class, 'cashFlow']);

// ===== PAYMENT GATEWAY =====
use App\Controllers\PaymentController;
$router->get('/payments', [PaymentController::class, 'index']);
$router->get('/payments/create', [PaymentController::class, 'create']);
$router->post('/payments/process', [PaymentController::class, 'process']);
$router->get('/payments/callback', [PaymentController::class, 'callback']);
$router->post('/payments/webhook', [PaymentController::class, 'webhook']);

// ===== DOCUMENT MANAGEMENT =====
use App\Controllers\DocumentController;
$router->get('/documents', [DocumentController::class, 'index']);
$router->get('/documents/templates', [DocumentController::class, 'templates']);
$router->get('/documents/templates/create', [DocumentController::class, 'createTemplate']);
$router->post('/documents/templates/store', [DocumentController::class, 'storeTemplate']);
$router->get('/documents/generate', [DocumentController::class, 'generate']);
$router->post('/documents/generate', [DocumentController::class, 'processGenerate']);

// ===== PAYROLL SYSTEM =====
use App\Controllers\PayrollController;
$router->get('/payroll', [PayrollController::class, 'index']);
$router->get('/payroll/employees', [PayrollController::class, 'employees']);
$router->get('/payroll/employees/create', [PayrollController::class, 'createEmployee']);
$router->post('/payroll/employees/store', [PayrollController::class, 'storeEmployee']);
$router->get('/payroll/process', [PayrollController::class, 'process']);
$router->post('/payroll/process', [PayrollController::class, 'runPayroll']);

// ===== COMPLIANCE MONITORING =====
use App\Controllers\ComplianceController;
$router->get('/compliance', [ComplianceController::class, 'index']);
$router->get('/compliance/checks', [ComplianceController::class, 'checks']);
$router->get('/compliance/reports', [ComplianceController::class, 'reports']);
$router->post('/compliance/check', [ComplianceController::class, 'runCheck']);

// ===== TENANT BACKUP MANAGEMENT =====
use App\Controllers\TenantBackupController as BackupController;
$router->get('/backup', [BackupController::class, 'index']);
$router->post('/backup/create', [BackupController::class, 'create']);
$router->get('/backup/download', [BackupController::class, 'download']);
$router->post('/backup/restore', [BackupController::class, 'restore']);

// ===== NAVIGATION MANAGEMENT =====
use App\Controllers\NavigationController;
$router->get('/navigation', [NavigationController::class, 'index']);
$router->post('/navigation/update', [NavigationController::class, 'update']);
$router->post('/navigation/reset', [NavigationController::class, 'reset']);

// ===== SUBSCRIPTION MANAGEMENT =====
use App\Controllers\SubscriptionController;
$router->get('/subscription', [SubscriptionController::class, 'index']);
$router->get('/subscription/plans', [SubscriptionController::class, 'plans']);
$router->post('/subscription/upgrade', [SubscriptionController::class, 'upgrade']);
$router->get('/subscription/billing', [SubscriptionController::class, 'billing']);

// ===== ADVANCED ACCOUNTING - SAK/PSAK/IFRS =====
use App\Controllers\AccountingController;
$router->get('/accounting', [AccountingController::class, 'index']);
$router->get('/accounting/chart', [AccountingController::class, 'index']);
$router->post('/accounting/chart/store', [AccountingController::class, 'store']);
$router->get('/accounting/chart/edit/{id}', [AccountingController::class, 'editAccount']);
$router->post('/accounting/chart/update/{id}', [AccountingController::class, 'updateAccount']);
$router->get('/accounting/journal', [AccountingController::class, 'journal']);
$router->get('/accounting/journal/create', [AccountingController::class, 'createJournal']);
$router->post('/accounting/journal/store', [AccountingController::class, 'storeJournal']);
$router->post('/accounting/journal/post/{id}', [AccountingController::class, 'postJournal']);
$router->get('/accounting/balance-sheet', [AccountingController::class, 'balanceSheet']);
$router->get('/accounting/income-statement', [AccountingController::class, 'incomeStatement']);
$router->get('/accounting/fixed-assets', [AccountingController::class, 'fixedAssets']);
$router->post('/accounting/depreciation/add', [AccountingController::class, 'addDepreciation']);
$router->get('/accounting/financial-instruments', [AccountingController::class, 'financialInstruments']);
$router->get('/accounting/adjustments', [AccountingController::class, 'journalAdjustments']);
$router->post('/accounting/adjustments/process', [AccountingController::class, 'processAdjustments']);

// ===== AUTO-DEBIT SYSTEM =====
use App\Controllers\AutoDebitController;
$router->get('/payments/auto-debit', [AutoDebitController::class, 'index']);
$router->get('/payments/auto-debit/create', [AutoDebitController::class, 'create']);
$router->post('/payments/auto-debit/store', [AutoDebitController::class, 'store']);
$router->post('/payments/auto-debit/process', [AutoDebitController::class, 'processAutoDebits']);
$router->get('/payments/auto-debit/transactions/{id}', [AutoDebitController::class, 'transactions']);
$router->post('/payments/auto-debit/deactivate/{id}', [AutoDebitController::class, 'deactivate']);

// ===== NOTIFICATION SYSTEM =====
use App\Controllers\NotificationController;
$router->get('/notifications/templates', [NotificationController::class, 'templates']);
$router->get('/notifications/templates/create', [NotificationController::class, 'createTemplate']);
$router->post('/notifications/templates/store', [NotificationController::class, 'storeTemplate']);
$router->get('/notifications/templates/edit/{id}', [NotificationController::class, 'editTemplate']);
$router->post('/notifications/templates/update/{id}', [NotificationController::class, 'updateTemplate']);
$router->post('/notifications/templates/test/{id}', [NotificationController::class, 'sendTest']);
$router->get('/notifications/logs', [NotificationController::class, 'logs']);
$router->post('/notifications/process-queue', [NotificationController::class, 'processQueue']);

// ===== ADVANCED ANALYTICS =====
use App\Controllers\AnalyticsController;
$router->get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
$router->get('/analytics/npl-forecast', [AnalyticsController::class, 'nplForecast']);
$router->get('/analytics/segmentation', [AnalyticsController::class, 'customerSegmentation']);
$router->get('/analytics/portfolio', [AnalyticsController::class, 'portfolioPerformance']);
$router->get('/analytics/risk-scoring', [AnalyticsController::class, 'riskScoring']);
$router->post('/analytics/risk-score/{id}', [AnalyticsController::class, 'calculateRiskScore']);
$router->get('/analytics/kpi-monitoring', [AnalyticsController::class, 'kpiMonitoring']);

// ===== MULTI-TENANT ANALYTICS =====

// ===== ADDITIONAL UTILITY ROUTES =====
$router->get('/profile', [UsersController::class, 'profile']);
$router->post('/profile/update', [UsersController::class, 'updateProfile']);

// ===== LANGUAGE SWITCHING =====
$router->post('/change-language', function() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $language = $input['language'] ?? 'id';
        
        // Validate language code
        $allowedLanguages = ['id', 'en'];
        if (!in_array($language, $allowedLanguages)) {
            throw new Exception('Invalid language code');
        }
        
        // Set language in session
        \App\Helpers\LanguageHelper::setLanguage($language);
        
        echo json_encode([
            'success' => true,
            'message' => 'Language changed successfully',
            'language' => $language
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
});

// Dispatch after all routes are registered
$router->dispatch($requestMethod, $uri);
