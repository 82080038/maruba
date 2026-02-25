<?php
/**
 * Cross-Impact URL Routing Fix
 * Fix all route_url() calls to use index.php prefix
 */

echo "=== CROSS-IMPACT URL ROUTING FIX ===\n\n";

$filesToCheck = [
    '/var/www/html/maruba/App/src/Views/accounting/income_statement.php',
    '/var/www/html/maruba/App/src/Views/accounting/chart_of_accounts.php',
    '/var/www/html/maruba/App/src/Views/accounting/trial_balance.php',
    '/var/www/html/maruba/App/src/Views/accounting/index.php',
    '/var/www/html/maruba/App/src/Views/accounting/balance_sheet.php',
    '/var/www/html/maruba/App/src/Views/accounting/cash_flow.php',
    '/var/www/html/maruba/App/src/Views/disbursement/index.php',
    '/var/www/html/maruba/App/src/Views/disbursement/create.php',
    '/var/www/html/maruba/App/src/Views/cooperative/register.php',
    '/var/www/html/maruba/App/src/Views/repayments/index.php',
    '/var/www/html/maruba/App/src/Views/repayments/create.php',
    '/var/www/html/maruba/App/src/Views/shu/index.php',
    '/var/www/html/maruba/App/src/Views/products/index.php',
    '/var/www/html/maruba/App/src/Views/products/create.php',
    '/var/www/html/maruba/App/src/Views/payments/index.php',
    '/var/www/html/maruba/App/src/Views/auth/register.php',
    '/var/www/html/maruba/App/src/Views/layout_dashboard.php',
    '/var/www/html/maruba/App/src/Views/subscription/index.php',
    '/var/www/html/maruba/App/src/Views/member/dashboard.php',
    '/var/www/html/maruba/App/src/Views/member/login.php',
    '/var/www/html/maruba/App/src/Views/dashboard/creator.php',
    '/var/www/html/maruba/App/src/Views/dashboard/surveyor.php',
    '/var/www/html/maruba/App/src/Views/dashboard/manajer.php',
    '/var/www/html/maruba/App/src/Views/dashboard/kasir.php',
    '/var/www/html/maruba/App/src/Views/dashboard/teller.php',
    '/var/www/html/maruba/App/src/Views/dashboard/collector.php',
    '/var/www/html/maruba/App/src/Views/dashboard/akuntansi.php',
    '/var/www/html/maruba/App/src/Views/dashboard/tenant.php',
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

$filesFixed = [];
$totalFiles = count($filesToCheck);

foreach ($filesToCheck as $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: {$file}\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check for route_url without index.php
    if (preg_match_all("/route_url\('([^']*)'\)/", $content, $matches)) {
        $needsFix = false;
        foreach ($matches[1] as $route) {
            // Skip if already has index.php or starts with http
            if (strpos($route, 'index.php/') === 0 || strpos($route, 'http') === 0) {
                continue;
            }
            $needsFix = true;
            break;
        }
        
        if ($needsFix) {
            echo "🔧 Fixing: " . basename($file) . "\n";
            
            // Fix all route_url calls
            $content = preg_replace_callback(
                "/route_url\('([^']*)'\)/",
                function($matches) {
                    $route = $matches[1];
                    // Skip if already has index.php or starts with http
                    if (strpos($route, 'index.php/') === 0 || strpos($route, 'http') === 0) {
                        return $matches[0];
                    }
                    return "route_url('index.php/' . ltrim($route, '/') . "')";
                },
                $content
            );
            
            // Write back the file
            file_put_contents($file, $content);
            $filesFixed[] = basename($file);
        } else {
            echo "✅ OK: " . basename($file) . "\n";
        }
    } else {
        echo "✅ OK: " . basename($file) . " (no route_url found)\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total files checked: {$totalFiles}\n";
echo "Files fixed: " . count($filesFixed) . "\n";

if (!empty($filesFixed)) {
    echo "🔧 Files Fixed:\n";
    foreach ($filesFixed as $file) {
        echo "  • {$file}\n";
    }
    echo "\n✅ All route_url() calls now use index.php prefix\n";
} else {
    echo "\n✅ All files already use correct routing\n";
}

echo "\n=== FIX COMPLETE ===\n";
?>
