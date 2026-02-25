<?php
/**
 * Fix jQuery Context Issues
 * Move all jQuery usage inside jQuery ready
 */

echo "=== FIXING JQUERY CONTEXT ISSUES ===\n\n";

$layoutFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($layoutFile);

// Find the main JavaScript section
preg_match('/<script[^>]*>(.*?)<\/script>/s', $content, $matches);

if (!isset($matches[1])) {
    echo "❌ No JavaScript section found\n";
    exit;
}

$jsContent = $matches[1];

// Find all jQuery ready blocks
preg_match_all('/\$\s*\(\s*document\s*\)\s*\.ready\s*\(\s*function\s*\(\s*\)\s*\{([^}]*(\{[^}]*\}[^}]*)*)\}\s*\);/s', $jsContent, $readyBlocks);

if (empty($readyBlocks[0])) {
    echo "❌ No jQuery ready blocks found\n";
    exit;
}

// Get the last jQuery ready block (main one)
$mainReadyBlock = end($readyBlocks[0]);

// Find all functions that use jQuery outside ready
preg_match_all('/function\s+(\w+)\s*\([^)]*\)\s*\{([^}]*(\{[^}]*\}[^}]*)*)\}/s', $jsContent, $functions);

echo "🔧 FOUND " . count($functions[0]) . " FUNCTIONS\n";

// Create new content with all jQuery usage moved to ready
$newContent = $jsContent;

// Move all jQuery-dependent functions inside the last ready block
foreach ($functions[0] as $index => $function) {
    $functionName = $function[1];
    $functionContent = $function[0];
    
    // Check if function uses jQuery
    if (strpos($functionContent, '$(') !== false) {
        echo "  🔄 Moving function {$functionName} to jQuery ready\n";
        
        // Remove the function from its current location
        $newContent = str_replace($functionContent, '', $newContent);
        
        // Add it to the main ready block
        $mainReadyBlock .= "\n\n        // Function: {$functionName}\n        {$functionContent}";
    }
}

// Replace the main ready block in the content
$newContent = preg_replace('/\$\s*\(\s*document\s*\)\s*\.ready\s*\(\s*function\s*\(\s*\)\s*\{([^}]*(\{[^}]*\}[^}]*)*)\}\s*\);/s', 
    '$(document).ready(function() {' . $mainReadyBlock . '});', 
    $newContent);

// Replace the entire JavaScript section
$content = preg_replace('/<script[^>]*>.*?<\/script>/s', '<script>' . $newContent . '</script>', $content);

// Write back the file
if (file_put_contents($layoutFile, $content)) {
    echo "✅ File updated successfully\n";
} else {
    echo "❌ Failed to update file\n";
}

echo "\n=== FIX COMPLETE ===\n";
?>
