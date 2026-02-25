<?php
/**
 * Simple jQuery Context Fix
 * Simple fix for jQuery context issues
 */

echo "=== SIMPLE JQUERY CONTEXT FIX ===\n\n";

$layoutFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($layoutFile);

// Find the last jQuery ready block
$lastReadyPos = strrpos($content, '$(document).ready(function()');

if ($lastReadyPos === false) {
    echo "❌ No jQuery ready block found\n";
    exit;
}

// Find the end of the last jQuery ready block
$endPos = strpos($content, '});', $lastReadyPos);
if ($endPos === false) {
    echo "❌ Could not find end of jQuery ready block\n";
    exit;
}

// Extract the content after the last ready block
$afterReady = substr($content, $endPos + 3);

// Find all jQuery usage in the after-ready section
$jqueryUsage = [];
$lines = explode("\n", $afterReady);
$lineNum = 0;

foreach ($lines as $line) {
    $lineNum++;
    $trimmed = trim($line);
    
    if (strpos($trimmed, '$(') !== false) {
        $jqueryUsage[] = $lineNum . ': ' . $trimmed;
    }
}

echo "🔍 FOUND " . count($jqueryUsage) . " LINES WITH JQUERY USAGE AFTER READY\n";

if (!empty($jqueryUsage)) {
    echo "📋 JQUERY USAGE LINES:\n";
    foreach ($jqueryUsage as $usage) {
        echo "  {$usage}\n";
    }
    
    echo "\n🔧 SOLUTION: Move all jQuery usage inside jQuery ready block\n";
    echo "⚠️  This requires manual editing of the file\n";
} else {
    echo "✅ No jQuery usage found after ready block\n";
}

echo "\n=== SIMPLE FIX COMPLETE ===\n";
?>
