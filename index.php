<?php
// Front controller - SINGLE ENTRY POINT
require_once __DIR__ . '/App/src/bootstrap.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
use App\Controllers\ApiController;
use App\Controllers\SuratController;
use App\Controllers\RegisterController;

$router = new Router();

// Get the request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

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

// Member routes
$router->get('/members', [MembersController::class, 'index']);
$router->get('/members/create', [MembersController::class, 'create']);
$router->post('/members/store', [MembersController::class, 'store']);

// Product routes
$router->get('/products', [ProductsController::class, 'index']);
$router->get('/products/create', [ProductsController::class, 'create']);
$router->post('/products/store', [ProductsController::class, 'store']);

// Survey routes
$router->get('/surveys', [SurveysController::class, 'index']);
$router->get('/surveys/create', [SurveysController::class, 'create']);
$router->post('/surveys/store', [SurveysController::class, 'store']);

// Repayment routes
$router->get('/repayments', [RepaymentsController::class, 'index']);
$router->get('/repayments/create', [RepaymentsController::class, 'create']);
$router->post('/repayments/store', [RepaymentsController::class, 'store']);

// Report routes
$router->get('/reports', [ReportsController::class, 'index']);
$router->get('/reports/export', [ReportsController::class, 'export']);

// User routes
$router->get('/users', [UsersController::class, 'index']);
$router->get('/users/create', [UsersController::class, 'create']);
$router->post('/users/store', [UsersController::class, 'store']);

// Audit route
$router->get('/audit', [AuditController::class, 'index']);

// Disbursement routes
$router->get('/disbursement', [DisbursementController::class, 'index']);
$router->get('/disbursement/create', [DisbursementController::class, 'create']);
$router->post('/disbursement/store', [DisbursementController::class, 'store']);

// Surat routes
$router->get('/surat', [SuratController::class, 'index']);
$router->get('/surat/lamaran-kerja', [SuratController::class, 'lamaranKerja']);
$router->get('/surat/permohonan-anggota', [SuratController::class, 'permohonanAnggota']);
$router->get('/surat/daftar-sah', [SuratController::class, 'daftarSah']);
$router->get('/surat/permohonan-pinjaman', [SuratController::class, 'permohonanPinjaman']);
$router->get('/surat/skb', [SuratController::class, 'skb']);

// API endpoints
$router->get('/api/members', [ApiController::class, 'members']);
$router->get('/api/surveys', [ApiController::class, 'surveys']);
$router->post('/api/members/geo', [ApiController::class, 'updateMemberGeo']);
$router->post('/api/surveys/geo', [ApiController::class, 'updateSurveyGeo']);
$router->get('/api/dashboard', [ApiController::class, 'dashboard']);
$router->get('/api/tenants', [ApiController::class, 'getTenants']);

// ===== SAVINGS SYSTEM =====
use App\Controllers\SavingsController;
$router->get('/savings', [SavingsController::class, 'index']);
$router->get('/savings/create', [SavingsController::class, 'create']);
$router->post('/savings/store', [SavingsController::class, 'store']);
$router->get('/savings/accounts', [SavingsController::class, 'accounts']);
$router->post('/savings/deposit', [SavingsController::class, 'deposit']);
$router->post('/savings/withdraw', [SavingsController::class, 'withdraw']);

// ===== SHU (SISA HASIL USAHA) SYSTEM =====
use App\Controllers\SHUController;
$router->get('/shu', [SHUController::class, 'index']);
$router->get('/shu/calculate', [SHUController::class, 'calculate']);
$router->post('/shu/calculate', [SHUController::class, 'processCalculation']);
$router->get('/shu/distribute', [SHUController::class, 'distribute']);
$router->post('/shu/distribute', [SHUController::class, 'processDistribution']);

// ===== ACCOUNTING SYSTEM =====
use App\Controllers\AccountingController;
$router->get('/accounting', [AccountingController::class, 'index']);
$router->get('/accounting/journal', [AccountingController::class, 'journal']);
$router->get('/accounting/journal/create', [AccountingController::class, 'createJournal']);
$router->post('/accounting/journal/store', [AccountingController::class, 'storeJournal']);
$router->get('/accounting/chart', [AccountingController::class, 'chartOfAccounts']);
$router->get('/accounting/reports', [AccountingController::class, 'reports']);

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
use App\Controllers\TenantBackupController;
$router->get('/backup', [TenantBackupController::class, 'index']);
$router->post('/backup/create', [TenantBackupController::class, 'create']);
$router->get('/backup/download', [TenantBackupController::class, 'download']);
$router->post('/backup/restore', [TenantBackupController::class, 'restore']);

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

// ===== MULTI-TENANT ANALYTICS =====
use App\Controllers\MultiTenantAnalyticsController;
$router->get('/analytics', [MultiTenantAnalyticsController::class, 'index']);
$router->get('/analytics/tenants', [MultiTenantAnalyticsController::class, 'tenants']);
$router->get('/analytics/performance', [MultiTenantAnalyticsController::class, 'performance']);
$router->get('/analytics/financial', [MultiTenantAnalyticsController::class, 'financial']);

// ===== ADDITIONAL UTILITY ROUTES =====
$router->get('/profile', [UsersController::class, 'profile']);
$router->post('/profile/update', [UsersController::class, 'updateProfile']);

// Dispatch the request
$router->dispatch($requestMethod, $uri);
