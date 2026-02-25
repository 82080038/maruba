<?php
/**
 * Complete JavaScript Fix
 * Remove ALL PHP code from JavaScript files
 */

echo "=== COMPLETE JAVASCRIPT FIX ===\n\n";

$jsFiles = [
    '/var/www/html/maruba/App/public/assets/js/ksp-ui-library.js',
    '/var/www/html/maruba/App/public/assets/js/ksp-components.js',
    '/var/www/html/maruba/App/public/assets/js/indonesian-format.js'
];

foreach ($jsFiles as $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: " . basename($file) . "\n";
        continue;
    }
    
    echo "🔧 Fixing: " . basename($file) . "\n";
    
    $content = file_get_contents($file);
    
    // Remove ALL PHP code
    $content = preg_replace('/<\?php.*?\?>/s', '', $content);
    
    // Remove PHP echo statements
    $content = preg_replace('/<\?=.*?\?>/s', '', $content);
    
    // Replace PHP variables with JavaScript equivalents
    $content = str_replace([
        "baseUrl: '<?= rtrim(route_url(''), '/') ?>',",
        "apiUrl: '<?= rtrim(route_url(''), '/') ?>/api',",
        "csrfToken: '<?= \$_SESSION['csrf_token'] ?? '' ?>',",
        "currentUser: <?= json_encode(\$_SESSION['user'] ?? null) ?>,"
    ], [
        "baseUrl: '/maruba',",
        "apiUrl: '/maruba/api',",
        "csrfToken: '',",
        "currentUser: null,"
    ], $content);
    
    // Write back the file
    if (file_put_contents($file, $content) !== false) {
        echo "✅ Fixed: " . basename($file) . "\n";
    } else {
        echo "❌ Failed: " . basename($file) . "\n";
    }
}

echo "\n=== VERIFYING FIX ===\n";

// Verify one file
$testFile = '/var/www/html/maruba/App/public/assets/js/ksp-ui-library.js';
$content = file_get_contents($testFile);

if (strpos($content, '<?') === false && strpos($content, '<?=') === false) {
    echo "✅ All PHP code removed from JavaScript files\n";
} else {
    echo "❌ PHP code still found in JavaScript files\n";
}

echo "\n=== FIX COMPLETE ===\n";
?>
