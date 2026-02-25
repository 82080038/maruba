<?php
/**
 * Mobile Navigation CSS Check
 * Check if there are any CSS issues blocking mobile menu
 */

echo "=== MOBILE NAVIGATION CSS CHECK ===\n\n";

$dashboardFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($dashboardFile);

echo "🔍 CSS ANALYSIS:\n";

// Extract CSS from the file
preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $content, $cssMatches);
$cssContent = isset($cssMatches[1][0]) ? $cssMatches[1][0] : '';

if (empty($cssContent)) {
    echo "❌ No CSS found in <style> tags\n";
} else {
    echo "✅ CSS found in <style> tags\n";
    
    // Check for mobile menu toggle CSS
    if (strpos($cssContent, '.mobile-menu-toggle') !== false) {
        echo "✅ .mobile-menu-toggle CSS found\n";
        
        // Extract mobile menu toggle CSS rules
        preg_match_all('/\.mobile-menu-toggle\s*{([^}]+)}/', $cssContent, $toggleRules);
        
        echo "📋 Mobile Menu Toggle CSS Rules:\n";
        foreach ($toggleRules[1] as $index => $rule) {
            echo "  Rule " . ($index + 1) . ": " . trim($rule) . "\n";
            
            // Check if display: none is present
            if (strpos($rule, 'display: none') !== false) {
                echo "    ⚠️  Contains 'display: none' (default)\n";
            }
            if (strpos($rule, 'display: block') !== false) {
                echo "    ✅ Contains 'display: block' (mobile)\n";
            }
            if (strpos($rule, 'display: inline-flex') !== false) {
                echo "    ✅ Contains 'display: inline-flex' (mobile)\n";
            }
        }
    } else {
        echo "❌ .mobile-menu-toggle CSS NOT found\n";
    }
    
    // Check for main-sidenav.show CSS
    if (strpos($cssContent, '.main-sidenav.show') !== false) {
        echo "✅ .main-sidenav.show CSS found\n";
        
        preg_match_all('/\.main-sidenav\.show\s*{([^}]+)}/', $cssContent, $sidebarRules);
        
        echo "📋 Sidebar Show CSS Rules:\n";
        foreach ($sidebarRules[1] as $index => $rule) {
            echo "  Rule " . ($index + 1) . ": " . trim($rule) . "\n";
            
            if (strpos($rule, 'transform: translateX(0)') !== false) {
                echo "    ✅ Contains 'transform: translateX(0)' (show)\n";
            }
        }
    } else {
        echo "❌ .main-sidenav.show CSS NOT found\n";
    }
    
    // Check media queries
    preg_match_all('/@media[^{]*{([^}]+)}/', $cssContent, $mediaQueries);
    
    echo "📱 Media Queries Found: " . count($mediaQueries[0]) . "\n";
    foreach ($mediaQueries[0] as $index => $query) {
        echo "  Query " . ($index + 1) . ": " . trim(substr($query, 0, 50)) . "...\n";
        
        if (strpos($query, 'max-width: 991') !== false) {
            echo "    ✅ Tablet breakpoint (991px)\n";
        }
        if (strpos($query, 'max-width: 767') !== false) {
            echo "    ✅ Mobile breakpoint (767px)\n";
        }
    }
}

echo "\n🎯 RECOMMENDATIONS:\n";
echo "1. Test in browser with mobile viewport\n";
echo "2. Check browser developer tools\n";
echo "3. Verify hamburger menu appears at < 992px\n";
echo "4. Test click functionality\n";

echo "\n=== CSS CHECK COMPLETE ===\n";
?>
