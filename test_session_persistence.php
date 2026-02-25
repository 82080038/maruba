<?php
/**
 * Session Persistence Test
 * Test if sessions are properly cleared on logout
 */

echo "=== SESSION PERSISTENCE TEST ===\n\n";

echo "🔍 TESTING SESSION PERSISTENCE:\n";
echo str_repeat("-", 50) . "\n";

// Test 1: Create session
echo "1. CREATING SESSION:\n";
session_start();
$_SESSION['test'] = 'session_data_' . time();
$_SESSION['user'] = [
    'username' => 'admin',
    'role' => 'admin',
    'name' => 'Admin Demo'
];
echo "✅ Session created with test data\n";
echo "  Session ID: " . session_id() . "\n";

// Test 2: Check if session persists
echo "\n2. CHECKING SESSION PERSISTENCE:\n";
session_write_close();
session_start();

if (isset($_SESSION['test'])) {
    echo "✅ Session persists: " . $_SESSION['test'] . "\n";
} else {
    echo "❌ Session lost\n";
}

if (isset($_SESSION['user'])) {
    echo "✅ User session persists: " . $_SESSION['user']['username'] . "\n";
} else {
    echo "❌ User session lost\n";
}

// Test 3: Simulate logout
echo "\n3. SIMULATING LOGOUT:\n";
session_destroy();

// Test 4: Check if session is truly destroyed
echo "\n4. CHECKING SESSION DESTRUCTION:\n";
session_start();

if (isset($_SESSION['test'])) {
    echo "❌ Session still exists after destroy\n";
} else {
    echo "✅ Session destroyed successfully\n";
}

if (isset($_SESSION['user'])) {
    echo "❌ User session still exists after destroy\n";
} else {
    echo "✅ User session destroyed successfully\n";
}

echo "\n🎯 SESSION STATUS:\n";
if (isset($_SESSION['user'])) {
    echo "❌ SESSION STILL ACTIVE - PROBLEM!\n";
    echo "  • Session not properly destroyed\n";
    echo "  • May need to check session configuration\n";
    echo "  • May need to check session cookie settings\n";
} else {
    echo "✅ SESSION PROPERLY DESTROYED\n";
    echo "  • Session data cleared\n";
    echo "  • User logged out successfully\n";
}

echo "\n🔧 COMMON SESSION ISSUES:\n";
echo "  • Session not destroyed: Check session_destroy() call\n";
echo "  • Session persists: Check session_write_close() before destroy\n";
echo "  • Cookie remains: Check cookie cleanup\n";
echo "  • Cache remains: Check cache clearing\n";

echo "\n🚀 NEXT STEPS:\n";
echo "  1. Test actual login/logout in browser\n";
echo " 2. Check browser developer tools for cookies\n";
echo " 3. Verify sidebar behavior\n";
echo " 4. Verify redirect behavior\n";

echo "\n=== SESSION PERSISTENCE TEST COMPLETE ===\n";
?>
