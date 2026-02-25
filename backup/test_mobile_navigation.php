<?php
/**
 * Mobile Navigation Test
 * Test mobile navigation functionality
 */

echo "=== MOBILE NAVIGATION TEST ===\n\n";

echo "📱 CHECKING MOBILE NAVIGATION COMPONENTS:\n";
echo str_repeat("-", 50) . "\n";

// Test 1: Check mobile menu toggle in HTML
echo "1. MOBILE MENU TOGGLE:\n";
$dashboardFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($dashboardFile);

if (strpos($content, 'mobile-menu-toggle') !== false) {
    echo "✅ Mobile menu toggle found in HTML\n";
} else {
    echo "❌ Mobile menu toggle NOT found in HTML\n";
}

if (strpos($content, 'id="mobileMenuToggle"') !== false) {
    echo "✅ Mobile menu toggle ID found\n";
} else {
    echo "❌ Mobile menu toggle ID NOT found\n";
}

// Test 2: Check CSS for mobile menu
echo "\n2. MOBILE MENU CSS:\n";
if (strpos($content, '@media (max-width: 991px)') !== false) {
    echo "✅ Mobile media query found\n";
} else {
    echo "❌ Mobile media query NOT found\n";
}

if (strpos($content, '.mobile-menu-toggle { display: block') !== false) {
    echo "✅ Mobile menu display CSS found\n";
} else {
    echo "❌ Mobile menu display CSS NOT found\n";
}

if (strpos($content, '.main-sidenav.show') !== false) {
    echo "✅ Sidebar show CSS found\n";
} else {
    echo "❌ Sidebar show CSS NOT found\n";
}

// Test 3: Check JavaScript functions
echo "\n3. JAVASCRIPT FUNCTIONS:\n";
if (strpos($content, 'initializeMobileMenu()') !== false) {
    echo "✅ initializeMobileMenu function found\n";
} else {
    echo "❌ initializeMobileMenu function NOT found\n";
}

if (strpos($content, '$(\'#mobileMenuToggle\').on(\'click\'') !== false) {
    echo "✅ Mobile menu click handler found\n";
} else {
    echo "❌ Mobile menu click handler NOT found\n";
}

if (strpos($content, '$(\'#mainSidenav\').toggleClass(\'show\')') !== false) {
    echo "✅ Sidebar toggle functionality found\n";
} else {
    echo "❌ Sidebar toggle functionality NOT found\n";
}

// Test 4: Check sidebar element
echo "\n4. SIDEBAR ELEMENT:\n";
if (strpos($content, 'id="mainSidenav"') !== false) {
    echo "✅ Sidebar ID found\n";
} else {
    echo "❌ Sidebar ID NOT found\n";
}

if (strpos($content, 'class="main-sidenav"') !== false) {
    echo "✅ Sidebar class found\n";
} else {
    echo "❌ Sidebar class NOT found\n";
}

// Test 5: Check responsive breakpoints
echo "\n5. RESPONSIVE BREAKPOINTS:\n";
if (strpos($content, 'max-width: 991px') !== false) {
    echo "✅ Tablet breakpoint (991px) found\n";
} else {
    echo "❌ Tablet breakpoint NOT found\n";
}

if (strpos($content, 'max-width: 767px') !== false) {
    echo "✅ Mobile breakpoint (767px) found\n";
} else {
    echo "❌ Mobile breakpoint NOT found\n";
}

echo "\n🎯 EXPECTED MOBILE BEHAVIOR:\n";
echo "  • Screen < 992px: Mobile menu toggle visible\n";
echo "  • Click toggle: Sidebar slides in/out\n";
echo "  • Click outside: Sidebar closes\n";
echo "  • Navigation: Sidebar slides out after navigation\n";

echo "\n🧪 MANUAL TEST INSTRUCTIONS:\n";
echo "  1. Open http://localhost/maruba/index.php/dashboard\n";
echo "  2. Resize browser to < 992px width\n";
echo "  3. Should see hamburger menu (☰) in header\n";
echo "  4. Click hamburger menu → Sidebar should slide in\n";
echo "  5. Click outside sidebar → Sidebar should slide out\n";
echo "  6. Click menu items → Navigation works, sidebar closes\n";

echo "\n=== MOBILE NAVIGATION TEST COMPLETE ===\n";
?>
