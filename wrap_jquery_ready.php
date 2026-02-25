<?php
/**
 * Wrap jQuery Usage with Ready
 * Wrap all jQuery usage outside ready with jQuery ready
 */

echo "=== WRAPPING JQUERY USAGE WITH READY ===\n\n";

$layoutFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($layoutFile);

// Find all jQuery usage outside ready blocks
$pattern = '/\$\([^)]*\)\.([^;]+);/s';
preg_match_all($pattern, $content, $matches);

echo "🔍 FOUND " . count($matches[0]) . " JQUERY USAGE PATTERNS\n";

if (!empty($matches[0])) {
    echo "📋 JQUERY USAGE FOUND:\n";
    foreach ($matches[0] as $index => $match) {
        echo "  " . ($index + 1) . ": {$match}\n";
    }
    
    // Replace each jQuery usage with ready wrapper
    $replacements = 0;
    foreach ($matches[0] as $match) {
        $replacement = "$(document).ready(function() {\n    {$match}\n});";
        $content = str_replace($match, $replacement, $content);
        $replacements++;
    }
    
    echo "\n🔧 REPLACEMENTS MADE: {$replacements}\n";
    
    // Write back the file
    if (file_put_contents($layoutFile, $content)) {
        echo "✅ File updated successfully\n";
    } else {
        echo "❌ Failed to update file\n";
    }
} else {
    echo "✅ No jQuery usage patterns found\n";
}

echo "\n=== WRAPPING COMPLETE ===\n";
?>
