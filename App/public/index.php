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
use App\Controllers\ReportsController;
use App\Controllers\UsersController;
use App\Controllers\AuditController;
use App\Controllers\DisbursementController;
use App\Controllers\ApiController;
use App\Controllers\SuratController;

$router = new Router();
$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/loans', [LoanController::class, 'index']);
$router->get('/loans/create', [LoanController::class, 'create']);
$router->post('/loans/store', [LoanController::class, 'store']);
$router->get('/members', [MembersController::class, 'index']);
$router->get('/members/create', [MembersController::class, 'create']);
$router->post('/members/store', [MembersController::class, 'store']);
$router->get('/products', [ProductsController::class, 'index']);
$router->get('/products/create', [ProductsController::class, 'create']);
$router->post('/products/store', [ProductsController::class, 'store']);
$router->get('/surveys', [SurveysController::class, 'index']);
$router->get('/surveys/create', [SurveysController::class, 'create']);
$router->post('/surveys/store', [SurveysController::class, 'store']);
$router->get('/repayments', [RepaymentsController::class, 'index']);
$router->get('/repayments/create', [RepaymentsController::class, 'create']);
$router->post('/repayments/store', [RepaymentsController::class, 'store']);
$router->get('/reports', [ReportsController::class, 'index']);
$router->get('/reports/export', [ReportsController::class, 'export']);
$router->get('/users', [UsersController::class, 'index']);
$router->get('/users/create', [UsersController::class, 'create']);
$router->post('/users/store', [UsersController::class, 'store']);
$router->get('/audit', [AuditController::class, 'index']);
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

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
