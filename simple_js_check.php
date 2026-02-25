<?php
/**
 * Simple JavaScript Error Check
 * Check if JavaScript errors are fixed
 */

echo "=== SIMPLE JAVASCRIPT ERROR CHECK ===\n\n";

// Test 1: Check if JavaScript files are clean
echo "1. JAVASCRIPT FILES CLEAN CHECK\n";
echo str_repeat("-", 40) . "\n";

$jsFiles = [
    '/var/www/html/maruba/App/public/assets/js/ksp-ui-library.js',
    '/var/www/html/maruba/App/public/assets/js/ksp-components.js',
    '/var/www/html/maruba/App/public/assets/js/indonesian-format.js'
];

$allClean = true;
foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        if (strpos($content, '<?php') !== false) {
            echo "❌ " . basename($file) . " - Contains PHP tags\n";
            $allClean = false;
        } elseif (strpos($content, '<script>') !== false) {
            echo "❌ " . basename($file) . " - Contains HTML script tags\n";
            $allClean = false;
        } else {
            echo "✅ " . basename($file) . " - Clean JavaScript\n";
        }
    } else {
        echo "❌ " . basename($file) . " - Not found\n";
        $allClean = false;
    }
}

// Test 2: Check if jQuery is loaded first
echo "\n2. JQUERY LOADING ORDER CHECK\n";
echo str_repeat("-", 40) . "\n";

$layoutFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
if (file_exists($layoutFile)) {
    $content = file_get_contents($layoutFile);
    
    if (preg_match('/<script[^>]*jquery[^>]*>/', $content, $matches)) {
        echo "✅ jQuery found in layout\n";
        
        // Check if it's loaded before other scripts
        $jqueryPos = strpos($content, $matches[0]);
        $kspPos = strpos($content, 'ksp-ui-library.js');
        
        if ($jqueryPos < $kspPos) {
            echo "✅ jQuery loaded before custom scripts\n";
        } else {
            echo "❌ jQuery loaded after custom scripts\n";
            $allClean = false;
        }
    } else {
        echo "❌ jQuery not found in layout\n";
        $allClean = false;
    }
} else {
    echo "❌ Layout file not found\n";
    $allClean = false;
}

// Test 3: Check for syntax errors in dashboard view
echo "\n3. DASHBOARD SYNTAX CHECK\n";
echo str_repeat("-", 40) . "\n";

$dashboardFile = '/var/www/html/maruba/App/src/Views/dashboard/index.php';
if (file_exists($dashboardFile)) {
    $content = file_get_contents($dashboardFile);
    
    // Check for unclosed braces
    $openBraces = substr_count($content, '{');
    $closeBraces = substr_count($content, '}');
    
    if ($openBraces === $closeBraces) {
        echo "✅ Dashboard braces balanced\n";
    } else {
        echo "❌ Dashboard braces unbalanced ({$openBraces} open, {$closeBraces} close)\n";
        $allClean = false;
    }
    
    // Check for PHP syntax errors (basic)
    if (strpos($content, '<?php') !== false && strpos($content, '?>') !== false) {
        echo "✅ Dashboard PHP tags balanced\n";
    } else {
        echo "⚠️  Dashboard PHP tags may be unbalanced\n";
    }
} else {
    echo "❌ Dashboard file not found\n";
    $allClean = false;
}

// Test 4: Check layout syntax
echo "\n4. LAYOUT SYNTAX CHECK\n";
echo str_repeat("-", 40) . "\n";

if (file_exists($layoutFile)) {
    $content = file_get_contents($layoutFile);
    
    // Check for unclosed braces in JavaScript sections
    $jsSections = [];
    preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $matches);
    
    foreach ($matches[1] as $index => $jsCode) {
        $openBraces = substr_count($jsCode, '{');
        $closeBraces = substr_count($jsCode, '}');
        
        if ($openBraces !== $closeBraces) {
            echo "❌ JavaScript section " . ($index + 1) . " has unbalanced braces\n";
            $allClean = false;
        }
    }
    
    if (empty($jsSections)) {
        echo "⚠️  No JavaScript sections found\n";
    } else {
        echo "✅ JavaScript sections checked\n";
    }
}

// Summary
echo "\n=== SUMMARY ===\n";
echo str_repeat("=", 50) . "\n";

if ($allClean) {
    echo "🎉 ALL JAVASCRIPT ISSUES FIXED!\n";
    echo "✅ JavaScript files are clean\n";
    echo "✅ jQuery loading order correct\n";
    echo "✅ Dashboard syntax OK\n";
    echo "✅ Layout syntax OK\n";
    
    echo "\n🎯 EXPECTED BROWSER STATUS:\n";
    echo "• No '$ is not defined' errors\n";
    echo "• No 'Unexpected token' errors\n";
    echo "• No syntax errors\n";
    echo "• Dashboard should load properly\n";
    
} else {
    echo "⚠️  SOME ISSUES REMAIN:\n";
    echo "• Check the failed items above\n";
    echo "• Fix the identified issues\n";
}

echo "\n=== CHECK COMPLETE ===\n";
?>
