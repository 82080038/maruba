<?php
/**
 * Fix Critical Files Only
 * Fix only the important files that are actually used
 */

echo "=== FIXING CRITICAL FILES ONLY ===\n\n";

// Critical files that need fixing (exclude test files and scripts)
$criticalFiles = [
    // Controllers
    '/var/www/html/maruba/App/src/Controllers/DisbursementController.php',
    '/var/www/html/maruba/App/src/Controllers/PaymentController.php',
    
    // Important Views
    '/var/www/html/maruba/App/src/Views/accounting/chart_of_accounts.php',
    '/var/www/html/maruba/App/src/Views/accounting/trial_balance.php',
    '/var/www/html/maruba/App/src/Views/accounting/index.php',
    '/var/www/html/maruba/App/src/Views/accounting/balance_sheet.php',
    '/var/www/html/maruba/App/src/Views/accounting/cash_flow.php',
    '/var/www/html/maruba/App/src/Views/disbursement/index.php',
    '/var/www/html/maruba/App/src/Views/disbursement/create.php',
    '/var/www/html/maruba/App/src/Views/repayments/index.php',
    '/var/www/html/maruba/App/src/Views/repayments/create.php',
    '/var/www/html/maruba/App/src/Views/shu/index.php',
    '/var/www/html/maruba/App/src/Views/products/index.php',
    '/var/www/html/maruba/App/src/Views/products/create.php',
    '/var/www/html/maruba/App/src/Views/payments/index.php',
    '/var/www/html/maruba/App/src/Views/subscription/index.php',
    '/var/www/html/maruba/App/src/Views/member/dashboard.php',
    '/var/www/html/maruba/App/src/Views/member/login.php',
    '/var/www/html/maruba/App/src/Views/surveys/index.php',
    '/var/www/html/maruba/App/src/Views/surveys/create.php',
    '/var/www/html/maruba/App/src/Views/users/index.php',
    '/var/www/html/maruba/App/src/Views/users/create.php',
    '/var/www/html/maruba/App/src/Views/loans/index.php',
    '/var/www/html/maruba/App/src/Views/loans/create.php',
    '/var/www/html/maruba/App/src/Views/surat/index.php',
    '/var/www/html/maruba/App/src/Views/reports/index.php',
    '/var/www/html/maruba/App/src/Views/savings/index.php',
    '/var/www/html/maruba/App/src/Views/savings/create.php',
    '/var/www/html/maruba/App/src/Views/payroll/index.php',
    '/var/www/html/maruba/App/src/Views/members/show.php',
    '/var/www/html/maruba/App/src/Views/members/index.php',
    '/var/www/html/maruba/App/src/Views/members/create.php',
    '/var/www/html/maruba/App/src/Views/members/edit.php',
    '/var/www/html/maruba/App/src/Views/tenant/index.php',
    '/var/www/html/maruba/App/src/Views/tenant/create.php',
    '/var/www/html/maruba/App/src/Views/tenant/edit.php',
    '/var/www/html/maruba/App/src/Views/tenant/billing.php',
    '/var/www/html/maruba/App/src/Views/tenant/view.php'
];

$fixedCount = 0;
$failedCount = 0;

foreach ($criticalFiles as $file) {
    if (!file_exists($file)) {
        echo "❌ Not found: " . basename($file) . "\n";
        $failedCount++;
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check for route_url without index.php
    if (preg_match_all("/route_url\('([^i][^']*)'\)/", $content, $matches)) {
        echo "🔧 Fixing: " . basename($file) . "\n";
        
        // Fix route_url calls
        $newContent = preg_replace_callback(
            "/route_url\('([^i][^']*)'\)/",
            function($matches) {
                $route = $matches[1];
                // Skip if already has index.php or starts with http
                if (strpos($route, 'index.php/') === 0 || strpos($route, 'http') === 0) {
                    return $matches[0];
                }
                return "route_url('index.php/" . ltrim($route, '/') . "')";
            },
            $content
        );
        
        if (file_put_contents($file, $newContent) !== false) {
            $fixedCount++;
            echo "✅ Fixed: " . basename($file) . "\n";
        } else {
            $failedCount++;
            echo "❌ Failed: " . basename($file) . "\n";
        }
    } else {
        echo "✅ OK: " . basename($file) . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Critical files processed: " . count($criticalFiles) . "\n";
echo "Files fixed: {$fixedCount}\n";
echo "Files failed: {$failedCount}\n";

echo "\n=== CRITICAL FIX COMPLETE ===\n";
?>
