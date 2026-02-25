<?php
/**
 * Test JavaScript Loading
 * Test if JavaScript files are loading correctly
 */

echo "=== TESTING JAVASCRIPT LOADING ===\n\n";

// Test 1: Check if files exist and are accessible
echo "1. FILE ACCESSIBILITY TEST\n";
echo str_repeat("-", 40) . "\n";

$jsFiles = [
    'ksp-ui-library.js' => 'http://localhost/maruba/App/public/assets/js/ksp-ui-library.js',
    'ksp-components.js' => 'http://localhost/maruba/App/public/assets/js/ksp-components.js',
    'indonesian-format.js' => 'http://localhost/maruba/App/public/assets/js/indonesian-format.js'
];

foreach ($jsFiles as $name => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✅ {$name}: HTTP {$httpCode}\n";
    } else {
        echo "❌ {$name}: HTTP {$httpCode}\n";
    }
}

// Test 2: Check content type
echo "\n2. CONTENT TYPE TEST\n";
echo str_repeat("-", 40) . "\n";

foreach ($jsFiles as $name => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    curl_close($ch);
    
    if (strpos($headers, 'Content-Type: text/javascript') !== false || strpos($headers, 'Content-Type: application/javascript') !== false) {
        echo "✅ {$name}: Correct content type\n";
    } else {
        echo "❌ {$name}: Wrong content type\n";
    }
}

// Test 3: Check if content is valid JavaScript
echo "\n3. CONTENT VALIDATION TEST\n";
echo str_repeat("-", 40) . "\n";

foreach ($jsFiles as $name => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);
    
    if (strpos($content, '<?php') !== false) {
        echo "❌ {$name}: Contains PHP code\n";
    } elseif (strpos($content, '<script>') !== false) {
        echo "❌ {$name}: Contains HTML script tags\n";
    } elseif (strpos($content, '//') !== false || strpos($content, '/*') !== false) {
        echo "✅ {$name}: Valid JavaScript content\n";
    } else {
        echo "⚠️  {$name}: Unknown content format\n";
    }
}

// Test 4: Dashboard page test
echo "\n4. DASHBOARD PAGE TEST\n";
echo str_repeat("-", 40) . "\n";

// Login first
$loginPage = file_get_contents('http://localhost/maruba/');
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage, $csrfMatch);
$csrfToken = $csrfMatch[1];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/maruba/index.php/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => 'admin',
    'password' => 'admin123',
    'csrf_token' => $csrfToken
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/js_test.txt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Get dashboard
curl_setopt($ch, CURLOPT_URL, 'http://localhost/maruba/index.php/dashboard');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/js_test.txt');
$dashboardContent = curl_exec($ch);
curl_close($ch);

// Check for script tags
if (strpos($dashboardContent, 'ksp-ui-library.js') !== false) {
    echo "✅ Dashboard includes ksp-ui-library.js\n";
} else {
    echo "❌ Dashboard missing ksp-ui-library.js\n";
}

if (strpos($dashboardContent, 'ksp-components.js') !== false) {
    echo "✅ Dashboard includes ksp-components.js\n";
} else {
    echo "❌ Dashboard missing ksp-components.js\n";
}

if (strpos($dashboardContent, 'indonesian-format.js') !== false) {
    echo "✅ Dashboard includes indonesian-format.js\n";
} else {
    echo "❌ Dashboard missing indonesian-format.js\n";
}

if (strpos($dashboardContent, 'jquery-3.7.1.min.js') !== false) {
    echo "✅ Dashboard includes jQuery\n";
} else {
    echo "❌ Dashboard missing jQuery\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
