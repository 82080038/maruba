<?php
/**
 * Sidebar Behavior Test
 * Test sidebar visibility during login/logout
 */

echo "=== SIDEBAR BEHAVIOR TEST ===\n\n";

echo "🔍 TESTING SIDEBAR BEHAVIOR:\n";
echo str_repeat("-", 50) . "\n";

$dashboardFile = '/var/www/html/maruba/App/src/Views/layout_dashboard.php';
$content = file_get_contents($dashboardFile);

// Test 1: Check sidebar HTML structure
echo "1. SIDEBAR HTML STRUCTURE:\n";
if (strpos($content, '<nav class="main-sidenav" id="mainSidenav">') !== false) {
    echo "  ✅ Sidebar element found\n";
} else {
    echo "  ❌ Sidebar element NOT found\n";
}

// Test 2: Check sidebar CSS classes
echo "\n2. SIDEBAR CSS CLASSES:\n";
if (strpos($content, 'class="main-sidenav"') !== false) {
    echo "  ✅ Sidebar class found\n";
} else {
    echo "  ❌ Sidebar class NOT found\n";
}

if (strpos($content, 'id="mainSidenav"') !== false) {
    echo "  ✅ Sidebar ID found\n";
} else {
    echo "  ❌ Sidebar ID NOT found\n";
}

// Test 3: Check sidebar visibility CSS
echo "\n3. SIDEBAR VISIBILITY CSS:\n";
if (strpos($content, '.main-sidenav.show') !== false) {
    echo "  ✅ Sidebar show CSS found\n";
} else {
    echo "  ❌ Sidebar show CSS NOT found\n";
}

if (strpos($content, 'transform: translateX(-100%)') !== false) {
    echo "  ✅ Sidebar hide CSS found\n";
} else {
    echo "  ❌ Sidebar hide CSS NOT found\n";
}

// Test 4: Check JavaScript sidebar functions
echo "\n4. JAVASCRIPT SIDEBAR FUNCTIONS:\n";
if (strpos($content, 'initializeMobileMenu()') !== false) {
    echo "  ✅ Mobile menu initialization found\n";
} else {
    echo "  ❌ Mobile menu initialization NOT found\n";
}

if (strpos($content, '$(\'#mainSidenav\').toggleClass(\'show\')') !== false) {
    echo "  ✅ Sidebar toggle function found\n";
} else {
    echo "  ❌ Sidebar toggle function NOT found\n";
}

// Test 5: Check session-dependent JavaScript
echo "\n5. SESSION-DEPENDENT JAVASCRIPT:\n";
if (strpos($content, 'serverRendered') !== false) {
    echo "  ✅ Server-rendered check found\n";
} else {
    echo "  ❌ Server-rendered check NOT found\n";
}

if (strpos($content, 'const serverRendered') !== false) {
    echo "  ✅ Server-rendered variable found\n";
} else {
    echo "  ❌ Server-rendered variable NOT found\n";
}

// Test 6: Check if sidebar is hidden by default
echo "\n6. SIDEBAR DEFAULT STATE:\n";
if (strpos($content, '@media (max-width: 991px)') !== false) {
    echo "  ✅ Mobile breakpoint found\n";
} else {
    echo "  ❌ Mobile breakpoint NOT found\n";
}

if (strpos($content, '.mobile-menu-toggle { display: block') !== false) {
    echo "  ✅ Mobile menu toggle CSS found\n";
} else {
    echo "  ❌ Mobile menu toggle CSS NOT found\n";
}

echo "\n🎯 EXPECTED BEHAVIOR:\n";
echo "  • Login: Sidebar should be visible on desktop\n";
echo "  • Login: Sidebar should be hidden on mobile (toggle available)\n";
echo "  • Logout: Sidebar should be hidden\n";
echo "  • Session cleared: Sidebar should reset to default\n";

echo "\n🧪 COMMON ISSUES:\n";
echo "  • Sidebar shows after logout: Session not cleared\n";
echo "  • Sidebar doesn't show after login: CSS issue\n";
echo "  • Sidebar stuck: JavaScript error\n";
echo "  • Sidebar flickers: CSS transition issue\n";

echo "\n🚀 MANUAL TEST REQUIRED:\n";
echo "  1. Open http://localhost/maruba/\n";
echo "  2. Login with admin/admin123\n";
echo "  3. Check sidebar visibility\n";
echo "  4. Click logout\n";
echo "  5. Check if sidebar disappears\n";
echo "  6. Check browser console for errors\n";

echo "\n=== SIDEBAR BEHAVIOR TEST COMPLETE ===\n";
?>
