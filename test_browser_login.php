<?php
/**
 * Quick Login Browser Test
 * Simulate browser quick login behavior
 */

echo "=== BROWSER QUICK LOGIN TEST ===\n\n";

// Get CSRF token from login page
$loginPage = file_get_contents('http://localhost/maruba/');
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage, $matches);
$csrfToken = $matches[1] ?? '';

echo "1. CSRF Token: " . substr($csrfToken, 0, 16) . "...\n";

// Test quick login data extraction
preg_match_all('/data-username="([^"]+)"/', $loginPage, $usernames);
preg_match_all('/data-password="([^"]+)"/', $loginPage, $passwords);
preg_match_all('/data-role="([^"]+)"/', $loginPage, $roles);

echo "2. Quick Login Data Found:\n";
foreach ($usernames[1] as $i => $username) {
    echo "   • {$username}/{$passwords[1][$i]} → {$roles[1][$i]}\n";
}

// Test form submission
echo "\n3. Testing Login Flow:\n";

// Test admin login
$postData = http_build_query([
    'username' => 'admin',
    'password' => 'admin123',
    'csrf_token' => $csrfToken
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/maruba/index.php/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Status: {$httpCode}\n";
if (strpos($response, 'dashboard') !== false) {
    echo "   ✅ Login successful - redirected to dashboard\n";
} elseif (strpos($response, 'login') !== false) {
    echo "   ❌ Login failed - still on login page\n";
} else {
    echo "   ⚠️  Unknown response\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
