<?php
/**
 * Find JavaScript Syntax Errors
 * Find specific syntax errors in JavaScript
 */

echo "=== FINDING JAVASCRIPT SYNTAX ERRORS ===\n\n";

$layoutFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($layoutFile);

// Extract JavaScript sections
preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $matches);

echo "🔍 JAVASCRIPT SECTIONS FOUND: " . count($matches[0]) . "\n";
echo str_repeat("-", 50) . "\n";

foreach ($matches[1] as $index => $jsCode) {
    echo "\n--- JAVASCRIPT SECTION " . ($index + 1) . " ---\n";
    
    // Check for syntax errors
    $lines = explode("\n", $jsCode);
    $lineNum = 0;
    
    foreach ($lines as $line) {
        $lineNum++;
        $trimmed = trim($line);
        
        // Check for common syntax errors
        if (strpos($trimmed, '$(') !== false && strpos($trimmed, ')') === false) {
            echo "Line {$lineNum}: Possible incomplete jQuery call - {$trimmed}\n";
        }
        
        if (strpos($trimmed, 'function(') !== false && strpos($trimmed, ')') === false) {
            echo "Line {$lineNum}: Possible incomplete function - {$trimmed}\n";
        }
        
        if (strpos($trimmed, 'if(') !== false && strpos($trimmed, ')') === false) {
            echo "Line {$lineNum}: Possible incomplete if statement - {$trimmed}\n";
        }
        
        if (strpos($trimmed, 'missing') !== false) {
            echo "Line {$lineNum}: Contains 'missing' - {$trimmed}\n";
        }
        
        if (strpos($trimmed, 'undefined') !== false) {
            echo "Line {$lineNum}: Contains 'undefined' - {$trimmed}\n";
        }
        
        if (strpos($trimmed, 'ReferenceError') !== false) {
            echo "Line {$lineNum}: Contains 'ReferenceError' - {$trimmed}\n";
        }
        
        if (strpos($trimmed, 'SyntaxError') !== false) {
            echo "Line {$lineNum}: Contains 'SyntaxError' - {$trimmed}\n";
        }
    }
}

echo "\n=== SYNTAX ERROR CHECK COMPLETE ===\n";
?>
