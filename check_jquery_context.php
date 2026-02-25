<?php
/**
 * Check jQuery Context Issues
 * Check if jQuery functions are called outside jQuery ready
 */

echo "=== CHECKING JQUERY CONTEXT ISSUES ===\n\n";

$layoutFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($layoutFile);

// Extract JavaScript sections
preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $matches);

$mainSection = $matches[1][count($matches[1]) - 1]; // Last section
$lines = explode("\n", $mainSection);

$inJQueryReady = false;
$contextIssues = [];
$lineNum = 0;

foreach ($lines as $line) {
    $lineNum++;
    $trimmed = trim($line);
    
    // Check if we're entering jQuery ready
    if (strpos($trimmed, '$(document).ready') !== false || strpos($trimmed, 'jQuery(function') !== false) {
        $inJQueryReady = true;
        echo "Line {$lineNum}: Entering jQuery ready context\n";
    }
    
    // Check if we're exiting jQuery ready
    if (strpos($trimmed, '});') !== false && $inJQueryReady) {
        // Check if this is the closing of jQuery ready
        if (substr_count($line, '}') > 0) {
            $inJQueryReady = false;
            echo "Line {$lineNum}: Exiting jQuery ready context\n";
        }
    }
    
    // Check for jQuery usage outside ready
    if (strpos($trimmed, '$(') !== false && !$inJQueryReady) {
        $contextIssues[] = "Line {$lineNum}: jQuery usage outside ready - {$trimmed}";
    }
    
    // Check for loadPartialPage calls outside ready
    if (strpos($trimmed, 'loadPartialPage') !== false && !$inJQueryReady) {
        $contextIssues[] = "Line {$lineNum}: loadPartialPage call outside ready - {$trimmed}";
    }
    
    // Check for event listeners outside ready
    if (strpos($trimmed, 'addEventListener') !== false && !$inJQueryReady) {
        $contextIssues[] = "Line {$lineNum}: Event listener outside ready - {$trimmed}";
    }
}

echo "\n🔍 CONTEXT ISSUES FOUND:\n";
if (empty($contextIssues)) {
    echo "✅ No jQuery context issues found\n";
} else {
    foreach ($contextIssues as $issue) {
        echo "❌ {$issue}\n";
    }
}

echo "\n🎯 RECOMMENDATIONS:\n";
echo "  • Move all jQuery usage inside $(document).ready()\n";
echo "  • Move all event listeners inside jQuery ready\n";
echo "  • Ensure loadPartialPage is defined before use\n";
echo "  • Check for any global jQuery references\n";

echo "\n=== CONTEXT CHECK COMPLETE ===\n";
?>
