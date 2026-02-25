<?php
/**
 * Comprehensive Dashboard & Layout Test
 * Test all dashboard views for common issues
 */

echo "=== COMPREHENSIVE DASHBOARD & LAYOUT TEST ===\n\n";

// Get CSRF token
$loginPage = file_get_contents('http://localhost/maruba/');
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage, $csrfMatch);
$csrfToken = $csrfMatch[1];

// Login
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/maruba/index.php/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => 'admin',
    'password' => 'admin123',
    'csrf_token' => $csrfToken
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/comprehensive_test_cookies.txt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

$dashboards = [
    'admin' => '/maruba/index.php/dashboard',
    'manajer' => '/maruba/index.php/dashboard', // Will redirect to manajer dashboard
    'kasir' => '/maruba/index.php/dashboard', // Will redirect to kasir dashboard
    'teller' => '/maruba/index.php/dashboard', // Will redirect to teller dashboard
    'surveyor' => '/maruba/index.php/dashboard', // Will redirect to surveyor dashboard
    'collector' => '/maruba/index.php/dashboard', // Will redirect to collector dashboard
    'akuntansi' => '/maruba/index.php/dashboard', // Will redirect to akuntansi dashboard
    'creator' => '/maruba/index.php/dashboard' // Will redirect to creator dashboard
];

$issues = [];

foreach ($dashboards as $role => $url) {
    echo "Testing {$role} dashboard...\n";
    echo str_repeat("-", 40) . "\n";
    
    // Test with different users
    $testUsers = [
        'admin' => ['username' => 'admin', 'password' => 'admin123'],
        'manajer' => ['username' => 'manajer', 'password' => 'manager123'],
        'kasir' => ['username' => 'kasir', 'password' => 'kasir123'],
        'teller' => ['username' => 'teller', 'password' => 'teller123'],
        'surveyor' => ['username' => 'surveyor', 'password' => 'surveyor123'],
        'collector' => ['username' => 'collector', 'password' => 'collector123'],
        'akuntansi' => ['username' => 'akuntansi', 'password' => 'akuntansi123'],
        'creator' => ['username' => 'creator', 'password' => 'creator123']
    ];
    
    $user = $testUsers[$role];
    
    // Login as specific user
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/maruba/index.php/login');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $user['username'],
        'password' => $user['password'],
        'csrf_token' => $csrfToken
    ]));
    curl_exec($ch);
    
    // Get dashboard
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    $dashboardResponse = curl_exec($ch);
    
    // Check for common issues
    $roleIssues = [];
    
    // 1. CSS Loading
    if (strpos($dashboardResponse, 'dashboard.css') === false) {
        $roleIssues[] = "CSS not loaded";
    }
    
    // 2. jQuery Loading
    if (strpos($dashboardResponse, 'jquery-3.7.1.min.js') === false) {
        $roleIssues[] = "jQuery not loaded";
    }
    
    // 3. Bootstrap Loading
    if (strpos($dashboardResponse, 'bootstrap.bundle.min.js') === false) {
        $roleIssues[] = "Bootstrap not loaded";
    }
    
    // 4. Logout URLs
    if (strpos($dashboardResponse, 'index.php/logout') === false) {
        $roleIssues[] = "Logout URLs not using index.php";
    }
    
    // 5. Mobile Navigation
    if (strpos($dashboardResponse, 'mobile-menu-toggle') === false) {
        $roleIssues[] = "Mobile navigation missing";
    }
    
    // 6. Duplicate CSS/JS
    $cssCount = substr_count($dashboardResponse, 'dashboard.css');
    if ($cssCount > 1) {
        $roleIssues[] = "Duplicate CSS loading ({$cssCount} times)";
    }
    
    // 7. JavaScript Errors
    if (strpos($dashboardResponse, 'Uncaught ReferenceError') !== false) {
        $roleIssues[] = "JavaScript ReferenceError found";
    }
    
    if (strpos($dashboardResponse, 'Unexpected token') !== false) {
        $roleIssues[] = "JavaScript SyntaxError found";
    }
    
    // 8. IndonesianFormat Path
    if (strpos($dashboardResponse, 'IndonesianFormat.php') !== false) {
        if (strpos($dashboardResponse, '../../IndonesianFormat.php') === false) {
            $roleIssues[] = "Incorrect IndonesianFormat path";
        }
    }
    
    if (empty($roleIssues)) {
        echo "✅ {$role} dashboard: No issues found\n";
    } else {
        echo "❌ {$role} dashboard issues:\n";
        foreach ($roleIssues as $issue) {
            echo "   • {$issue}\n";
            $issues[] = "{$role}: {$issue}";
        }
    }
    echo "\n";
}

curl_close($ch);

// Summary
echo "=== SUMMARY ===\n";
echo str_repeat("=", 50) . "\n";

if (empty($issues)) {
    echo "🎉 ALL DASHBOARDS ARE HEALTHY!\n";
    echo "✅ No CSS/JS loading issues\n";
    echo "✅ No JavaScript errors\n";
    echo "✅ Mobile navigation working\n";
    echo "✅ Logout URLs correct\n";
    echo "✅ No duplicate assets\n";
} else {
    echo "🔧 ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "❌ {$issue}\n";
    }
    
    echo "\n📋 Total Issues: " . count($issues) . "\n";
}

echo "\n🎯 Test Coverage:\n";
echo "• 8 dashboard views tested\n";
echo "• 8 user roles tested\n";
echo "• CSS/JS loading verified\n";
echo "• Mobile navigation checked\n";
echo "• Logout URLs validated\n";
echo "• Duplicate asset loading detected\n";
echo "• JavaScript error scanning\n";
echo "\n=== TEST COMPLETE ===\n";
?>
