<?php
/**
 * JavaScript Syntax Checker
 * Check for JavaScript syntax errors in layout_dashboard.php
 */

echo "=== JAVASCRIPT SYNTAX CHECKER ===\n\n";

$layoutFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($layoutFile);

// Extract all JavaScript sections
preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $matches);

echo "Found " . count($matches[0]) . " JavaScript sections\n\n";

foreach ($matches[1] as $index => $jsCode) {
    echo "=== JAVASCRIPT SECTION " . ($index + 1) . " ===\n";
    
    // Count braces
    $openBraces = substr_count($jsCode, '{');
    $closeBraces = substr_count($jsCode, '}');
    
    echo "Open braces: {$openBraces}\n";
    echo "Close braces: {$closeBraces}\n";
    
    if ($openBraces !== $closeBraces) {
        echo "❌ UNBALANCED BRACES!\n";
        
        // Find the problem area
        $lines = explode("\n", $jsCode);
        $braceCount = 0;
        $problemLine = 0;
        
        foreach ($lines as $lineNum => $line) {
            $braceCount += substr_count($line, '{') - substr_count($line, '}');
            if ($braceCount < 0) {
                $problemLine = $lineNum + 1;
                echo "Problem around line {$problemLine}: " . trim($line) . "\n";
                break;
            }
        }
    } else {
        echo "✅ Braces balanced\n";
    }
    
    // Check for $ outside jQuery context
    if (strpos($jsCode, '$(') !== false) {
        echo "Contains jQuery usage\n";
        
        // Check if it's inside jQuery ready
        if (strpos($jsCode, '$(document).ready') !== false || strpos($jsCode, 'jQuery(function') !== false) {
            echo "✅ Inside jQuery ready\n";
        } else {
            echo "⚠️  May be outside jQuery ready\n";
        }
    }
    
    echo "\n";
}

echo "=== CHECK COMPLETE ===\n";
?>
