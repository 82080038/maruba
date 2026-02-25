<?php
/**
 * Login/Logout Session Test
 * Test session behavior during login and logout
 */

echo "=== LOGIN/LOGOUT SESSION TEST ===\n\n";

echo "🔍 TESTING SESSION BEHAVIOR:\n";
echo str_repeat("-", 50) . "\n";

// Test 1: Check current session state
echo "1. CURRENT SESSION STATE:\n";
session_start();

if (isset($_SESSION['user'])) {
    echo "✅ User logged in: " . $_SESSION['user']['username'] . "\n";
    echo "  Role: " . $_SESSION['user']['role'] . "\n";
    echo "  Session ID: " . session_id() . "\n";
} else {
    echo "❌ No active session\n";
}

// Test 2: Simulate login
echo "\n2. SIMULATE LOGIN:\n";
$_SESSION['user'] = [
    'username' => 'admin',
    'role' => 'admin',
    'name' => 'Admin Demo',
    'id' => 1
];
echo "✅ User session created\n";

// Test 3: Check sidebar menu visibility
echo "\n3. SIDEBAR MENU VISIBILITY:\n";
$dashboardFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($dashboardFile);

// Check if sidebar is hidden by default
if (strpos($content, 'main-sidenav') !== false) {
    echo "✅ Sidebar element found in layout\n";
    
    // Check if sidebar is hidden by default
    if (strpos($content, 'transform: translateX(-100%)') !== false) {
        echo "✅ Sidebar hidden by default (mobile)\n";
    } else {
        echo "⚠️  Sidebar visible by default\n";
    }
} else {
    echo "❌ Sidebar element NOT found in layout\n";
}

// Test 4: Check logout behavior
echo "\n4. LOGOUT BEHAVIOR:\n";
echo "  • Session will be destroyed\n";
echo "  • Sidebar should hide\n";
echo "  • User will be redirected to login\n";

echo "\n🎯 EXPECTED BEHAVIOR:\n";
echo "  • Login: Sidebar should be visible\n";
echo "  • Logout: Sidebar should hide\n";
echo "  • Session should be cleared\n";
echo "  • User should be redirected to login\n";

echo "\n🧪 TROUBLESHOOTING:\n";
echo "  • If sidebar shows after logout: Session not cleared\n";
echo "  • If sidebar doesn't show after login: CSS issue\n";
echo "  • If redirect fails: Route issue\n";

echo "\n🚀 MANUAL TEST REQUIRED:\n";
echo "  1. Open http://localhost/maruba/\n";
echo " 2. Login with admin/admin123\n";
echo " 3. Check if sidebar appears\n";
echo " 4. Click logout\n";
echo " 5. Verify sidebar disappears\n";
echo " 6. Verify redirect to login\n";

echo "\n=== SESSION TEST COMPLETE ===\n";
?>
