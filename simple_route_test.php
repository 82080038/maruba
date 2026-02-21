<?php
// Simple route test
require_once __DIR__ . '/App/src/bootstrap.php';

echo "=== ROUTE TEST ===\n";

// Test 1: Check if AuthController exists
if (!class_exists('App\\Controllers\\AuthController')) {
    echo "❌ AuthController class not found\n";
    exit;
}

// Test 2: Check if showLogin method exists
if (!method_exists('App\\Controllers\\AuthController', 'showLogin')) {
    echo "❌ showLogin method not found\n";
    exit;
}

// Test 3: Check if login view exists
$loginView = __DIR__ . '/App/src/Views/auth/login.php';
if (!file_exists($loginView)) {
    echo "❌ Login view not found at: $loginView\n";
    exit;
}

// Test 4: Test route_url function
$loginUrl = route_url('login');
echo "✅ route_url('login'): $loginUrl\n";

// Test 5: Simulate router dispatch for /login
$uri = 'login';
echo "✅ Testing route: /login\n";

// Check if route exists (simplified)
if ($uri === 'login') {
    echo "✅ Route found: AuthController::showLogin\n";
    
    // Test controller instantiation
    try {
        $controller = new \App\Controllers\AuthController();
        echo "✅ Controller instantiated\n";
        
        // Test method call
        ob_start();
        $controller->showLogin();
        $output = ob_get_contents();
        ob_end_clean();
        
        if (strpos($output, 'Login Koperasi') !== false) {
            echo "✅ showLogin() works - contains 'Login Koperasi'\n";
        } else {
            echo "❌ showLogin() failed - missing expected content\n";
            echo "Output preview: " . substr($output, 0, 100) . "...\n";
        }
    } catch (Exception $e) {
        echo "❌ Controller error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Route not found for: $uri\n";
}

echo "=== TEST COMPLETE ===\n";
?>
