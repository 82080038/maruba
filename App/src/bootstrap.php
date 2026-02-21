<?php
// Basic bootstrap for the coop app

// Production: Hide errors (uncomment for production)
// ini_set('display_errors', '0');
// error_reporting(0);

// DEBUG: tampilkan error sementara (non-production)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// BASE_URL untuk subdir /maruba
if (!defined('BASE_URL')) {
    define('BASE_URL', '/maruba');
}
if (!defined('PUBLIC_URL')) {
    define('PUBLIC_URL', BASE_URL . '/App/public');
}

// Load .env if present (root or App/.env)
$envFiles = [__DIR__ . '/../.env', __DIR__ . '/../App/.env'];
foreach ($envFiles as $envFile) {
    if (!file_exists($envFile)) continue;
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Tentukan APP_NAME dengan fallback, hilangkan tanda kutip pembungkus jika ada
if (!defined('APP_NAME')) {
    $appNameEnv = $_ENV['APP_NAME'] ?? getenv('APP_NAME') ?? 'Koperasi App';
    // trim kutip single/double di awal-akhir
    $appNameEnv = trim($appNameEnv, "\"' ");
    define('APP_NAME', $appNameEnv ?: 'Koperasi App');
}

// Simple PSR-4-like autoloader for App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Convenience aliases
use App\Helpers\UiHelper;
use App\Middleware\TenantMiddleware;

// Start session for auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic helpers
function view_path(string $view, string $layout = 'layout'): string
{
    return __DIR__ . '/Views/' . $view . '.php';
}

// UI helper wrappers
function t(string $key): string { return UiHelper::t($key); }
function format_number($value, int $decimals = 0): string { return UiHelper::formatNumber($value, $decimals); }
function format_currency($value): string { return UiHelper::formatRupiah($value); }
function format_date_id($dateString, bool $withDay = false): string { return UiHelper::formatDateId($dateString, $withDay); }
function format_phone(string $phone): string { return UiHelper::formatPhone($phone); }
function format_npwp(string $npwp): string { return UiHelper::formatNpwp($npwp); }

function asset_url(string $path): string
{
    // Direct URL ke App/public (tanpa index.php)
    return rtrim(PUBLIC_URL, '/') . '/' . ltrim($path, '/');
}

// CSRF helpers
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    $token = $_POST['_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF token mismatch');
    }
}

function route_url(string $path = ''): string
{
    // Clean URL untuk SPA - tanpa index.php
    $base = rtrim(BASE_URL, '/');
    
    // Handle special cases for legacy support
    if ($path === '/') {
        return $base . '/';
    }
    
    if ($path !== '') {
        $url = $base . '/' . ltrim($path, '/');
    } else {
        $url = $base;
    }
    
    return $url;
}

// Legacy function for index.php URLs (deprecated)
function legacy_route_url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $url = $base . '/index.php';
    if ($path !== '') {
        $url .= '/' . ltrim($path, '/');
    }
    return $url;
}

// Auth helpers
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function user_role(): ?string
{
    return current_user()['role'] ?? null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . route_url(''));
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if (user_role() !== $role) {
        http_response_code(403);
        echo 'Akses ditolak.';
        exit;
    }
}

