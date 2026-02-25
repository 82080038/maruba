<?php
/**
 * Find JavaScript Brace Issues
 * Find specific line with brace problems
 */

echo "=== FINDING JAVASCRIPT BRACE ISSUES ===\n\n";

$layoutFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($layoutFile);

// Extract the main JavaScript section
preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $matches);

$mainSection = $matches[1][count($matches[1]) - 1]; // Last section
$lines = explode("\n", $mainSection);

$braceCount = 0;
$problemLines = [];

foreach ($lines as $lineNum => $line) {
    $lineBraces = substr_count($line, '{') - substr_count($line, '}');
    $braceCount += $lineBraces;
    
    if ($braceCount < 0) {
        $problemLines[] = "Line " . ($lineNum + 1) . ": " . trim($line) . " (EXTRA CLOSING BRACE)";
    }
}

if (!empty($problemLines)) {
    echo "❌ FOUND BRACE ISSUES:\n";
    foreach ($problemLines as $issue) {
        echo "  {$issue}\n";
    }
    
    echo "\n🔧 SOLUTION:\n";
    echo "   1. Find the line with extra closing brace\n";
    echo "  2. Remove the extra '}' character\n";
    echo "  3. Ensure proper brace nesting\n";
} else {
    echo "✅ No brace issues found in main JavaScript section\n";
}

echo "\n=== BRACE CHECK COMPLETE ===\n";
?>
