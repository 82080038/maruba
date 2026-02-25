<?php
/**
 * Dashboard Access Test for All Roles
 * Tests if each role can access their dashboard and has appropriate content
 */

echo "=== DASHBOARD ACCESS TEST FOR ALL ROLES ===\n\n";

// Role credentials
$roles = [
    'admin' => ['password' => 'admin123', 'expected_content' => ['admin', 'users', 'management']],
    'manajer' => ['password' => 'manager123', 'expected_content' => ['manajer', 'loans', 'approval']],
    'kasir' => ['password' => 'kasir123', 'expected_content' => ['kasir', 'cash', 'transactions']],
    'teller' => ['password' => 'teller123', 'expected_content' => ['teller', 'savings', 'members']],
    'surveyor' => ['password' => 'surveyor123', 'expected_content' => ['surveyor', 'surveys', 'field']],
    'collector' => ['password' => 'collector123', 'expected_content' => ['collector', 'repayments', 'collection']],
    'akuntansi' => ['password' => 'akuntansi123', 'expected_content' => ['akuntansi', 'accounting', 'reports']],
    'creator' => ['password' => 'creator123', 'expected_content' => ['creator', 'system', 'admin']]
];

// Get CSRF token
$loginPage = file_get_contents('http://localhost/maruba/');
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage, $csrfMatch);
$csrfToken = $csrfMatch[1];

$results = [];

foreach ($roles as $username => $data) {
    echo "Testing {$username} dashboard...\n";
    echo str_repeat("-", 40) . "\n";
    
    // Step 1: Login
    $postData = http_build_query([
        'username' => $username,
        'password' => $data['password'],
        'csrf_token' => $csrfToken
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/maruba/index.php/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/{$username}_cookies.txt");
    curl_setopt($ch, CURLOPT_COOKIEFILE, "/tmp/{$username}_cookies.txt");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $loginResponse = curl_exec($ch);
    
    // Step 2: Access dashboard
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/maruba/index.php/dashboard');
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    
    $dashboardResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Analyze results
    $result = [
        'login_success' => strpos($loginResponse, 'dashboard') !== false || $httpCode === 200,
        'dashboard_accessible' => $httpCode === 200,
        'dashboard_content' => [],
        'has_errors' => strpos($dashboardResponse, 'error') !== false || strpos($dashboardResponse, 'Error') !== false,
        'is_empty' => empty(trim($dashboardResponse)) || strlen($dashboardResponse) < 1000
    ];
    
    // Check for expected content
    foreach ($data['expected_content'] as $content) {
        if (stripos($dashboardResponse, $content) !== false) {
            $result['dashboard_content'][] = $content;
        }
    }
    
    // Check for common dashboard elements
    $commonElements = [
        'dashboard' => 'Dashboard header/title',
        'menu' => 'Navigation menu',
        'user' => 'User information',
        'kpi' => 'KPI/metrics',
        'chart' => 'Charts/graphs',
        'table' => 'Data tables'
    ];
    
    foreach ($commonElements as $keyword => $description) {
        if (stripos($dashboardResponse, $keyword) !== false) {
            $result['dashboard_elements'][] = $description;
        }
    }
    
    $results[$username] = $result;
    
    // Display results
    echo "🔐 Login: " . ($result['login_success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
    echo "📊 Dashboard Access: " . ($result['dashboard_accessible'] ? '✅ ACCESSIBLE' : '❌ NOT ACCESSIBLE') . "\n";
    echo "📄 Content Found: " . implode(', ', $result['dashboard_content']) . "\n";
    echo "🎯 Elements: " . implode(', ', $result['dashboard_elements'] ?? ['None']) . "\n";
    echo "⚠️  Errors: " . ($result['has_errors'] ? '❌ YES' : '✅ NO') . "\n";
    echo "📝 Empty: " . ($result['is_empty'] ? '❌ YES' : '✅ NO') . "\n";
    
    // Show sample content
    if ($result['dashboard_accessible'] && !$result['is_empty']) {
        echo "📋 Sample Content (first 200 chars):\n";
        echo "   " . substr(strip_tags($dashboardResponse), 0, 200) . "...\n";
    }
    
    echo "\n";
}

// Summary
echo "=== SUMMARY ===\n";
echo str_repeat("=", 50) . "\n";

$successfulRoles = [];
$failedRoles = [];

foreach ($results as $username => $result) {
    if ($result['login_success'] && $result['dashboard_accessible'] && !$result['has_errors'] && !$result['is_empty']) {
        $successfulRoles[] = $username;
    } else {
        $failedRoles[] = $username;
    }
}

echo "✅ Successful Roles: " . count($successfulRoles) . "/8\n";
echo "   • " . implode("\n   • ", $successfulRoles) . "\n\n";

if (!empty($failedRoles)) {
    echo "❌ Failed Roles: " . count($failedRoles) . "/8\n";
    echo "   • " . implode("\n   • ", $failedRoles) . "\n\n";
    
    echo "🔧 Issues Found:\n";
    foreach ($failedRoles as $username) {
        $result = $results[$username];
        echo "   {$username}:\n";
        if (!$result['login_success']) echo "     - Login failed\n";
        if (!$result['dashboard_accessible']) echo "     - Dashboard not accessible\n";
        if ($result['has_errors']) echo "     - Contains errors\n";
        if ($result['is_empty']) echo "     - Dashboard empty\n";
    }
} else {
    echo "🎉 ALL ROLES HAVE FUNCTIONAL DASHBOARDS!\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
