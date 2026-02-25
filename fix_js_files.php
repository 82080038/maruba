<?php
/**
 * Fix JavaScript Files - Remove PHP Tags
 * Remove PHP tags from all JavaScript files
 */

echo "=== FIXING JAVASCRIPT FILES - REMOVE PHP TAGS ===\n\n";

$jsFiles = [
    '/var/www/html/maruba/App/public/assets/js/ksp-ui-library.js',
    '/var/www/html/maruba/App/public/assets/js/ksp-components.js',
    '/var/www/html/maruba/App/public/assets/js/indonesian-format.js'
];

$fixedFiles = [];
$failedFiles = [];

foreach ($jsFiles as $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: " . basename($file) . "\n";
        $failedFiles[] = basename($file);
        continue;
    }
    
    echo "🔧 Fixing: " . basename($file) . "\n";
    
    $content = file_get_contents($file);
    
    // Remove PHP tags and comments
    $newContent = preg_replace('/^<\?php.*?\?>\s*/s', '', $content);
    
    // Remove <script> tags if present
    $newContent = preg_replace('/^<script>\s*/', '', $newContent);
    $newContent = preg_replace('/\s*<\/script>\s*$/', '', $newContent);
    
    // Write back the file
    if (file_put_contents($file, $newContent) !== false) {
        $fixedFiles[] = basename($file);
        echo "✅ Fixed: " . basename($file) . "\n";
    } else {
        $failedFiles[] = basename($file);
        echo "❌ Failed: " . basename($file) . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files processed: " . count($jsFiles) . "\n";
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

echo "\n=== VERIFYING FIX ===\n";

// Verify one file
if (!empty($fixedFiles)) {
    $firstFile = '/var/www/html/maruba/App/public/assets/js/' . $fixedFiles[0];
    $content = file_get_contents($firstFile);
    
    if (strpos($content, '<?php') === false && strpos($content, '<script>') === false) {
        echo "✅ Verification successful - PHP tags removed\n";
    } else {
        echo "❌ Verification failed - PHP tags still present\n";
    }
}

echo "\n=== FIX COMPLETE ===\n";
?>
