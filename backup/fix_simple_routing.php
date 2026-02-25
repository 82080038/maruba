<?php
/**
 * Simple URL Routing Fix
 */

echo "=== SIMPLE URL ROUTING FIX ===\n\n";

// List of files to check
$files = [
    '/var/www/html/maruba/App/src/Views/accounting/income_statement.php',
    '/var/www/html/maruba/App/src/Views/auth/register.php',
    '/var/www/html/maruba/App/src/Views/loans/create.php',
    '/var/www/html/maruba/App/src/Views/members/create.php'
];

$fixed = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: " . basename($file) . "\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Simple pattern matching for route_url without index.php
    if (preg_match("/route_url\('([^']+)'\)/", $content, $matches)) {
        $route = $matches[1];
        if (strpos($route, 'index.php/') === 0 || strpos($route, 'http') === 0) {
            echo "✅ OK: " . basename($file) . " (already correct)\n";
        } else {
            // Fix the route_url call
            $newContent = str_replace(
                "route_url('{$route}')",
                "route_url('index.php/{$route}')",
                $content
            );
            file_put_contents($file, $newContent);
            echo "🔧 Fixed: " . basename($file) . "\n";
            $fixed++;
        }
    } else {
        echo "✅ OK: " . basename($file) . " (no route_url)\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files checked: " . count($files) . "\n";
echo "Files fixed: {$fixed}\n";

if ($fixed > 0) {
    echo "\n🎯 Route URLs now use index.php prefix\n";
} else {
    echo "\n✅ All routes already correct\n";
}

echo "\n=== DONE ===\n";
?>
