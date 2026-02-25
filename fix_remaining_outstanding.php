<?php
/**
 * Fix Outstanding Balance Issues
 * Fix all remaining files that still use outstanding_balance
 */

echo "=== FIXING REMAINING OUTSTANDING_BALANCE ISSUES ===\n\n";

$filesToFix = [
    '/var/www/html/maruba/App/src/Api/MobileApiController.php',
    '/var/www/html/maruba/App/src/Compliance/ComplianceManager.php',
    '/var/www/html/maruba/App/src/Controllers/MemberPortalController.php',
    '/var/www/html/maruba/App/src/Controllers/AutoDebitController.php',
    '/var/www/html/maruba/App/src/Monitoring/TenantPerformanceMonitor.php',
    '/var/www/html/maruba/App/src/Dashboard/RealTimeDashboardEngine.php'
];

$fixedFiles = [];
$failedFiles = [];

foreach ($filesToFix as $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: " . basename($file) . "\n";
        $failedFiles[] = basename($file);
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if file contains outstanding_balance
    if (strpos($content, 'outstanding_balance') === false) {
        echo "✅ OK: " . basename($file) . " (no outstanding_balance found)\n";
        continue;
    }
    
    echo "🔧 Fixing: " . basename($file) . "\n";
    
    // Replace all occurrences of outstanding_balance with amount
    $newContent = str_replace('outstanding_balance', 'amount', $content);
    
    // Special case for UPDATE statements - we need to be more careful
    $newContent = preg_replace(
        '/SET\s+amount\s*=\s*amount\s*-\s*\?/',
        'SET amount = amount - ?',
        $newContent
    );
    
    // Write back the file
    if (file_put_contents($file, $newContent) !== false) {
        $fixedFiles[] = basename($file);
        echo "✅ Fixed: " . basename($file) . "\n";
    } else {
        $failedFiles[] = basename($file);
        echo "❌ Failed to fix: " . basename($file) . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files processed: " . count($filesToFix) . "\n";
echo "Files fixed: " . count($fixedFiles) . "\n";
echo "Files failed: " . count($failedFiles) . "\n";

if (!empty($fixedFiles)) {
    echo "\n🔧 Files Fixed:\n";
    foreach ($fixedFiles as $file) {
        echo "  • {$file}\n";
    }
}

if (!empty($failedFiles)) {
    echo "\n❌ Files Failed:\n";
    foreach ($failedFiles as $file) {
        echo "  • {$file}\n";
    }
}

// Verify no more outstanding_balance references
echo "\n=== VERIFICATION ===\n";
$remainingFiles = [];
foreach ($filesToFix as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'outstanding_balance') !== false) {
            $remainingFiles[] = basename($file);
        }
    }
}

if (empty($remainingFiles)) {
    echo "✅ All files fixed successfully!\n";
} else {
    echo "⚠️  Still contains outstanding_balance:\n";
    foreach ($remainingFiles as $file) {
        echo "  • {$file}\n";
    }
}

echo "\n=== FIX COMPLETE ===\n";
?>
