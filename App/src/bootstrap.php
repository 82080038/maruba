<?php
// Basic bootstrap for the coop app

// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

// Define constants from environment
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? 'true');
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Maruba Koperasi');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/maruba');

// Production: Hide errors (uncomment for production)
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
} else {
    // DEBUG: tampilkan error sementara (non-production)
    ini_set("display_errors", "1");
    error_reporting(E_ALL);
}

// BASE_URL untuk subdir /maruba
if (!defined('BASE_URL')) {
    define('BASE_URL', '/maruba');
}
if (!defined('PUBLIC_URL')) {
    define('PUBLIC_URL', BASE_URL . '/App/public');
}

// Database constants
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'maruba');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'root');

// Load helpers
require_once __DIR__ . '/Helpers/AuthHelper.php';
require_once __DIR__ . '/Helpers/LanguageHelper.php';

// Load core classes
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Router.php';

// Security constants
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'default_jwt_secret');
define('CSRF_TOKEN_SECRET', $_ENV['CSRF_TOKEN_SECRET'] ?? 'default_csrf_secret');
// ALL SESSION CONFIGURATION - MUST BE BEFORE session_start()
ini_set('session.cookie_secure', APP_ENV === 'production');
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 7200);

// Start session BEFORE any other operations that might send headers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token after session is started
generate_csrf_token();
// Timezone
date_default_timezone_set('Asia/Jakarta');

// Register autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Set security headers

// CORS headers for API
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
}

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
}

// Load helper functions
function view_path($view) {
    // Views live under App/src/Views
    return __DIR__ . '/Views/' . $view . '.php';
}

function asset_url($asset) {
    // Serve assets from the public directory
    return PUBLIC_URL . '/' . ltrim($asset, '/');
}

function route_url($route) {
    return BASE_URL . '/' . ltrim($route, '/');
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function user_role() {
    $user = current_user();
    return $user['role'] ?? null;
}

function legacy_route_url($route = '') {
    return BASE_URL . '/' . ltrim($route, '/');
}

function require_login() {
    if (!current_user()) {
        header('Location: ' . route_url('auth/login'));
    }
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        }
    }
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Error handler
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    if (APP_DEBUG === 'true') {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    
    error_log("Error: $message in $file on line $line");
    return true;
});

// Exception handler
set_exception_handler(function($exception) {
    if (APP_DEBUG === 'true') {
        echo "Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    } else {
        error_log("Exception: " . $exception->getMessage());
    }
});

// Shutdown function
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (APP_DEBUG === 'true') {
            echo "Fatal error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'];
        } else {
            error_log("Fatal error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        }
    }
});
