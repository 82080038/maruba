<?php
// Front controller
require __DIR__ . '/../src/bootstrap.php';

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\LoanController;
use App\Controllers\MembersController;
use App\Controllers\ProductsController;
use App\Controllers\SurveysController;
use App\Controllers\RepaymentsController;
use App\Controllers\NavigationController;
use App\Controllers\UsersController;
use App\Controllers\AccountingController;
use App\Controllers\SubscriptionController;
use App\Controllers\SHUController;

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

// Initialize tenant middleware (handles database switching)
$tenantMiddleware = new TenantMiddleware();
$tenantMiddleware->handle();

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

// Dashboard route
$router->get('/dashboard', [DashboardController::class, 'index']);

// Subscription Management routes
$router->get('/subscriptions', [SubscriptionController::class, 'index']);
$router->get('/subscription/tenant', [SubscriptionController::class, 'showTenant']);
$router->post('/subscription/upgrade', [SubscriptionController::class, 'upgrade']);
$router->post('/subscription/downgrade', [SubscriptionController::class, 'downgrade']);
$router->post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
$router->post('/subscription/reactivate', [SubscriptionController::class, 'reactivate']);

// Subscription Plan Management routes
$router->get('/subscription/manage-plans', [SubscriptionController::class, 'managePlans']);
$router->get('/subscription/create-plan', [SubscriptionController::class, 'createPlan']);
$router->post('/subscription/store-plan', [SubscriptionController::class, 'storePlan']);
$router->get('/subscription/edit-plan', [SubscriptionController::class, 'editPlan']);
$router->post('/subscription/update-plan', [SubscriptionController::class, 'updatePlan']);

// Public subscription plans
$router->get('/plans', [SubscriptionController::class, 'plans']);

// Loan routes
$router->get('/loans', [LoanController::class, 'index']);
$router->get('/loans/create', [LoanController::class, 'create']);
$router->post('/loans/store', [LoanController::class, 'store']);

// Member routes
$router->get('/members', [MembersController::class, 'index']);
$router->get('/members/create', [MembersController::class, 'create']);
$router->post('/members/store', [MembersController::class, 'store']);
$router->get('/members/show', [MembersController::class, 'show']);
$router->get('/members/edit', [MembersController::class, 'edit']);
$router->post('/members/update', [MembersController::class, 'update']);
$router->post('/members/delete', [MembersController::class, 'delete']);

// Public member registration routes
$router->get('/members/register', [MembersController::class, 'register']);
$router->post('/members/register', [MembersController::class, 'storeRegistration']);
$router->get('/members/registration-success', [MembersController::class, 'registrationSuccess']);

// Member verification routes
$router->get('/members/verify', [MembersController::class, 'verify']);
$router->get('/members/pending-verifications', [MembersController::class, 'pendingVerifications']);

// Product routes
$router->get('/products', [ProductsController::class, 'index']);
$router->get('/products/create', [ProductsController::class, 'create']);
$router->post('/products/store', [ProductsController::class, 'store']);

// Survey routes
$router->get('/surveys', [SurveysController::class, 'index']);
$router->get('/surveys/create', [SurveysController::class, 'create']);
$router->post('/surveys/store', [SurveysController::class, 'store']);
$router->get('/surveys/show', [SurveysController::class, 'show']);
$router->get('/surveys/export', [SurveysController::class, 'exportSurveys']);

// Payment routes
$router->get('/payments', [PaymentController::class, 'index']);
$router->get('/payments/create', [PaymentController::class, 'create']);
$router->post('/payments/store', [PaymentController::class, 'store']);
$router->get('/payments/show', [PaymentController::class, 'show']);
$router->get('/payments/confirm', [PaymentController::class, 'confirmPayment']);
$router->post('/payments/process', [PaymentController::class, 'processPayment']);

// Member payment routes
$router->get('/member/payments', [PaymentController::class, 'memberPayments']);
$router->get('/member/create-payment', [PaymentController::class, 'createMemberPayment']);

// Report routes
$router->get('/reports', [ReportsController::class, 'index']);
$router->get('/reports/export', [ReportsController::class, 'export']);

// Payroll routes
$router->get('/payroll', [PayrollController::class, 'index']);
$router->post('/payroll/generate', [PayrollController::class, 'generate']);
$router->get('/payroll/show', [PayrollController::class, 'show']);
$router->post('/payroll/approve', [PayrollController::class, 'approve']);
$router->post('/payroll/mark-paid', [PayrollController::class, 'markPaid']);
$router->get('/payroll/salary-slip', [PayrollController::class, 'salarySlip']);
$router->get('/payroll/my-payroll', [PayrollController::class, 'myPayroll']);
$router->post('/payroll/bulk-approve', [PayrollController::class, 'bulkApprove']);
$router->get('/payroll/export', [PayrollController::class, 'exportPayroll']);

// Accounting routes
$router->get('/accounting', [AccountingController::class, 'index']);
$router->get('/accounting/journals', [AccountingController::class, 'journals']);
$router->get('/accounting/create-journal', [AccountingController::class, 'createJournal']);
$router->post('/accounting/store-journal', [AccountingController::class, 'storeJournal']);
$router->get('/accounting/show-journal', [AccountingController::class, 'showJournal']);
$router->post('/accounting/post-journal', [AccountingController::class, 'postJournal']);
$router->get('/accounting/general-ledger', [AccountingController::class, 'generalLedger']);
$router->get('/accounting/trial-balance', [AccountingController::class, 'trialBalance']);
$router->get('/accounting/balance-sheet', [AccountingController::class, 'balanceSheet']);
$router->get('/accounting/income-statement', [AccountingController::class, 'incomeStatement']);
$router->get('/accounting/chart-of-accounts', [AccountingController::class, 'chartOfAccounts']);
$router->get('/accounting/export-trial-balance', [AccountingController::class, 'exportTrialBalance']);
$router->get('/accounting/export-general-ledger', [AccountingController::class, 'exportGeneralLedger']);

// Disbursement routes
$router->get('/disbursement', [DisbursementController::class, 'index']);
$router->get('/disbursement/create', [DisbursementController::class, 'create']);
$router->post('/disbursement/store', [DisbursementController::class, 'store']);

// Document routes
$router->get('/documents', [DocumentController::class, 'index']);
$router->get('/documents/templates', [DocumentController::class, 'templates']);
$router->get('/documents/create-template', [DocumentController::class, 'createTemplate']);
$router->post('/documents/store-template', [DocumentController::class, 'storeTemplate']);
$router->get('/documents/show-template', [DocumentController::class, 'showTemplate']);
$router->get('/documents/edit-template', [DocumentController::class, 'editTemplate']);
$router->post('/documents/update-template', [DocumentController::class, 'updateTemplate']);
$router->get('/documents/generate', [DocumentController::class, 'generateDocument']);
$router->get('/documents/show', [DocumentController::class, 'show']);
$router->get('/documents/download', [DocumentController::class, 'download']);
$router->post('/documents/update-status', [DocumentController::class, 'updateStatus']);

// Savings routes
$router->get('/savings', [SavingsController::class, 'index']);
$router->get('/savings/create', [SavingsController::class, 'create']);
$router->post('/savings/store', [SavingsController::class, 'store']);
$router->get('/savings/show', [SavingsController::class, 'show']);
$router->post('/savings/deposit', [SavingsController::class, 'deposit']);
$router->post('/savings/withdraw', [SavingsController::class, 'withdraw']);
$router->get('/savings/export', [SavingsController::class, 'exportSavings']);

// Tenant Customization routes
$router->get('/tenant/customization', [TenantCustomizationController::class, 'index']);
$router->post('/tenant/customization/theme', [TenantCustomizationController::class, 'updateTheme']);
$router->post('/tenant/customization/branding', [TenantCustomizationController::class, 'updateBranding']);
$router->post('/tenant/customization/ui-preferences', [TenantCustomizationController::class, 'updateUIPreferences']);
$router->post('/tenant/customization/upload-logo', [TenantCustomizationController::class, 'uploadLogo']);
$router->post('/tenant/customization/upload-favicon', [TenantCustomizationController::class, 'uploadFavicon']);
$router->post('/tenant/customization/reset', [TenantCustomizationController::class, 'resetCustomization']);
$router->get('/tenant/customization/css', [TenantCustomizationController::class, 'getTenantCSS']);
$router->post('/tenant/customization/preview-theme', [TenantCustomizationController::class, 'previewTheme']);

// Navigation Management routes
$router->get('/tenant/navigation/manage', [NavigationController::class, 'manageMenu']);
$router->post('/tenant/navigation/setup', [NavigationController::class, 'setupTenantMenu']);
$router->post('/tenant/navigation/add-item', [NavigationController::class, 'addMenuItem']);
$router->post('/tenant/navigation/update-item', [NavigationController::class, 'updateMenuItem']);
$router->get('/tenant/navigation/toggle-item', [NavigationController::class, 'toggleMenuItem']);
$router->get('/tenant/navigation/remove-item', [NavigationController::class, 'removeMenuItem']);
$router->post('/tenant/navigation/reorder', [NavigationController::class, 'reorderMenu']);

// API route for dynamic menu generation
$router->get('/api/navigation/menu', [NavigationController::class, 'getUserMenu']);

// Member Portal routes
$router->get('/member', [MemberPortalController::class, 'login']);
$router->get('/member/login', [MemberPortalController::class, 'login']);
$router->post('/member/authenticate', [MemberPortalController::class, 'authenticate']);
$router->get('/member/logout', [MemberPortalController::class, 'logout']);
$router->get('/member/dashboard', [MemberPortalController::class, 'dashboard']);
$router->get('/member/loans', [MemberPortalController::class, 'loans']);
$router->get('/member/loan-detail', [MemberPortalController::class, 'loanDetail']);
$router->get('/member/repayments', [MemberPortalController::class, 'repayments']);
$router->get('/member/savings', [MemberPortalController::class, 'savings']);
$router->get('/member/savings-detail', [MemberPortalController::class, 'savingsDetail']);
$router->get('/member/profile', [MemberPortalController::class, 'profile']);
$router->post('/member/profile/update', [MemberPortalController::class, 'updateProfile']);

// API endpoints
$router->get('/api/members', [ApiController::class, 'members']);
$router->get('/api/surveys', [ApiController::class, 'surveys']);
$router->post('/api/members/geo', [ApiController::class, 'updateMemberGeo']);
$router->post('/api/surveys/geo', [ApiController::class, 'updateSurveyGeo']);

// New API endpoints
$router->get('/api/dashboard', [ApiController::class, 'dashboard']);
$router->get('/api/members/list', [ApiController::class, 'getMembers']);
$router->get('/api/members/detail', [ApiController::class, 'getMember']);
$router->post('/api/members/create', [ApiController::class, 'createMember']);
$router->post('/api/members/update', [ApiController::class, 'updateMember']);
$router->get('/api/loans/list', [ApiController::class, 'getLoans']);
$router->get('/api/loans/detail', [ApiController::class, 'getLoan']);
$router->post('/api/loans/create', [ApiController::class, 'createLoan']);
$router->post('/api/loans/update', [ApiController::class, 'updateLoan']);

// Tenant Management API (main application only)
$router->get('/api/tenants', [ApiController::class, 'getTenants']);
$router->get('/api/tenants/detail', [ApiController::class, 'getTenant']);
$router->post('/api/tenants/create', [ApiController::class, 'createTenant']);
$router->post('/api/tenants/update', [ApiController::class, 'updateTenant']);
$router->post('/api/tenants/delete', [ApiController::class, 'deleteTenant']);
$router->get('/api/tenants/billing', [ApiController::class, 'getTenantBilling']);

// Tenant Dashboard API (called from tenant subdomains)
$router->get('/api/tenant/dashboard', [ApiController::class, 'tenantDashboard']);

// Billing Management API
$router->get('/api/billings', [ApiController::class, 'getBillings']);
$router->post('/api/billings/create', [ApiController::class, 'createBilling']);
$router->post('/api/billings/payment', [ApiController::class, 'recordBillingPayment']);
$router->post('/api/billings/generate-monthly', [ApiController::class, 'generateMonthlyBillings']);
$router->get('/api/billings/stats', [ApiController::class, 'getBillingStats']);
$router->post('/api/calculate-tenant-cost', [ApiController::class, 'calculateTenantCost']);

// Dispatch the request
$router->dispatch($requestMethod, $uri);
