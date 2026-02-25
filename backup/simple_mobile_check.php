<?php
/**
 * Simple Mobile Navigation Fix Verification
 * Simple check for mobile navigation components
 */

echo "=== SIMPLE MOBILE NAVIGATION CHECK ===\n\n";

$dashboardFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($dashboardFile);

echo "📱 MOBILE NAVIGATION COMPONENTS STATUS:\n\n";

// Check HTML elements
echo "✅ HTML Elements:\n";
if (strpos($content, 'mobile-menu-toggle') !== false) {
    echo "  • Mobile menu toggle: FOUND\n";
} else {
    echo "  • Mobile menu toggle: MISSING\n";
}

if (strpos($content, 'id="mobileMenuToggle"') !== false) {
    echo "  • Mobile menu ID: FOUND\n";
} else {
    echo "  • Mobile menu ID: MISSING\n";
}

if (strpos($content, 'id="mainSidenav"') !== false) {
    echo "  • Sidebar ID: FOUND\n";
} else {
    echo "  • Sidebar ID: MISSING\n";
}

// Check CSS
echo "\n✅ CSS Components:\n";
if (strpos($content, '@media (max-width: 991') !== false) {
    echo "  • Tablet breakpoint: FOUND\n";
} else {
    echo "  • Tablet breakpoint: MISSING\n";
}

if (strpos($content, '@media (max-width: 767') !== false) {
    echo "  • Mobile breakpoint: FOUND\n";
} else {
    echo "  • Mobile breakpoint: MISSING\n";
}

if (strpos($content, '.main-sidenav.show') !== false) {
    echo "  • Sidebar show CSS: FOUND\n";
} else {
    echo "  • Sidebar show CSS: MISSING\n";
}

// Check JavaScript
echo "\n✅ JavaScript Functions:\n";
if (strpos($content, 'initializeMobileMenu') !== false) {
    echo "  • Mobile menu init: FOUND\n";
} else {
    echo "  • Mobile menu init: MISSING\n";
}

if (strpos($content, '$(\'#mobileMenuToggle\')') !== false) {
    echo "  • Click handler: FOUND\n";
} else {
    echo "  • Click handler: MISSING\n";
}

if (strpos($content, '$(\'#mainSidenav\').toggleClass(\'show\')') !== false) {
    echo "  • Toggle function: FOUND\n";
} else {
    echo "  • Toggle function: MISSING\n";
}

echo "\n🎯 MANUAL TEST REQUIRED:\n";
echo "Please test manually in browser:\n";
echo "1. Open http://localhost/maruba/index.php/dashboard\n";
echo "2. Resize browser to mobile width (< 992px)\n";
echo "3. Look for hamburger menu in header\n";
echo "4. Click to test sidebar toggle\n";

echo "\n=== CHECK COMPLETE ===\n";
?>
