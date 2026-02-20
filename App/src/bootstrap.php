<?php
// Basic bootstrap for the coop app

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

// Load .env if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
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

// Start session for auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic helpers
function view_path(string $view, string $layout = 'layout'): string
{
    return __DIR__ . '/Views/' . $view . '.php';
}

function asset_url(string $path): string
{
    // Direct URL ke App/public (tanpa index.php)
    return rtrim(PUBLIC_URL, '/') . '/' . ltrim($path, '/');
}

function route_url(string $path = ''): string
{
    // Gunakan index.php agar aman meski mod_rewrite belum aktif
    $base = rtrim(BASE_URL, '/');
    $url = $base . '/index.php';
    if ($path !== '') {
        $url .= '/' . ltrim($path, '/');
    }
    return $url;
}

// Formatting helpers (server-side)
function format_number($value, int $decimals = 0): string
{
    $n = is_numeric($value) ? (float)$value : 0;
    return number_format($n, $decimals, ',', '.');
}

function format_currency($value): string
{
    return 'Rp ' . format_number($value, 0);
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

