<?php
/**
 * Session Configuration Test
 * Test session configuration and behavior
 */

echo "=== SESSION CONFIGURATION TEST ===\n\n";

echo "🔍 SESSION CONFIGURATION:\n";
echo str_repeat("-", 50) . "\n";

// Test 1: Check session settings
echo "1. SESSION SETTINGS:\n";
echo "  Session save path: " . session_save_path() . "\n";
echo "  Session name: " . session_name() . "\n";
echo "  Session ID: " . session_id() . "\n";
echo "  Session status: " . (session_status() === PHP_SESSION_NONE ? 'None' : 'Active') . "\n";

// Test 2: Check cookies
echo "\n2. COOKIE SETTINGS:\n";
if (isset($_COOKIE[session_name()])) {
    echo "  Session cookie exists: " . $_COOKIE[session_name()] . "\n";
} else {
    echo "  No session cookie found\n";
}

// Test 3: Check session garbage collection
echo "\n3. SESSION GARBAGE COLLECTION:\n";
echo "  Session lifetime: " . ini_get('session.gc_maxlifetime') . " seconds\n";
echo "  Session probability: " . ini_get('session.gc_probability') . "\n";
echo "  Session divisor: " . ini_get('session.gc_divisor') . "\n";

// Test 4: Check if session is properly started
echo "\n4. SESSION START TEST:\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "  ✅ Session started successfully\n";
} else {
    echo "  ⚠️  Session already active\n";
}

// Test 5: Create test session
echo "\n5. CREATE TEST SESSION:\n";
$_SESSION['test_logout'] = [
    'user' => 'admin',
    'timestamp' => time(),
    'data' => 'test_data'
];
echo "  ✅ Test session created\n";

// Test 6: Check session persistence
echo "\n6. CHECK SESSION PERSISTENCE:\n";
session_write_close();
session_start();

if (isset($_SESSION['test_logout'])) {
    echo "  ✅ Session persists: " . $_SESSION['test_logout']['user'] . "\n";
} else {
    echo "  ❌ Session lost\n";
}

// Test 7: Simulate logout
echo "\n7. SIMULATE LOGOUT:\n";
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();

// Test 8: Check if session is truly destroyed
echo "\n8. CHECK SESSION DESTRUCTION:\n";
session_start();

if (isset($_SESSION['test_logout'])) {
    echo "  ❌ Session still exists after logout\n";
    echo "  ⚠️  This may cause sidebar to persist\n";
} else {
    echo "  ✅ Session destroyed successfully\n";
}

echo "\n🎯 RECOMMENDATIONS:\n";
echo "  • If session persists: Check session_write_close() usage\n";
echo "  • If cookie remains: Check cookie path and domain\n";
echo "  • If cache remains: Check browser cache clearing\n";
echo "  • If sidebar persists: Check JavaScript session checks\n";

echo "\n🚀 NEXT STEPS:\n";
echo "  1. Test actual login/logout in browser\n";
echo "  2. Check browser developer tools\n";
echo "  3. Verify sidebar behavior\n";
echo "  4. Verify session clearing\n";

echo "\n=== SESSION CONFIGURATION TEST COMPLETE ===\n";
?>
