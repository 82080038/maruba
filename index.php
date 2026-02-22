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

// Dispatch the request
$router->dispatch($requestMethod, $uri);
