#!/bin/bash

# Fix PHP Syntax Errors Script
# This script fixes the 9 identified PHP syntax errors

echo "🔧 Fixing PHP Syntax Errors..."

# Fix 1: trial_balance.php line 42
echo "Fixing trial_balance.php..."
sed -i '42s/<span class="badge badge-/<span class="badge badge-/' /opt/lampp/htdocs/maruba/App/src/Views/accounting/trial_balance.php

# Fix 2: chart_of_accounts.php line 21  
echo "Fixing chart_of_accounts.php..."
sed -i '21s/<a href="<?= route_url/echo "<a href="<?= route_url/' /opt/lampp/htdocs/maruba/App/src/Views/accounting/chart_of_accounts.php

# Fix 3: AICreditScoringEngine.php line 159
echo "Fixing AICreditScoringEngine.php..."
sed -i '159s/\*\*/\*/' /opt/lampp/htdocs/maruba/App/src/AI/AICreditScoringEngine.php

# Fix 4: CacheManager.php line 393
echo "Fixing CacheManager.php..."
# Remove duplicate function
sed -i '393,400d' /opt/lampp/htdocs/maruba/App/src/Caching/CacheManager.php

# Fix 5: OCRDocumentProcessor.php line 529
echo "Fixing OCRDocumentProcessor.php..."
sed -i '529s/db\(/db(/' /opt/lampp/htdocs/maruba/App/src/OCR/OCRDocumentProcessor.php

# Fix 6: RLSPolicyManager.php line 284
echo "Fixing RLSPolicyManager.php..."
sed -i '284s/\?\?/\?/' /opt/lampp/htdocs/maruba/App/src/Database/Security/RLSPolicyManager.php

# Fix 7: PPOBService.php line 340
echo "Fixing PPOBService.php..."
sed -i '340s/db\(/db(/' /opt/lampp/htdocs/maruba/App/src/Services/PPOBService.php

# Fix 8: DigitalSignatureEngine.php line 520
echo "Fixing DigitalSignatureEngine.php..."
sed -i '520s/db\(/db(/' /opt/lampp/htdocs/maruba/App/src/Signature/DigitalSignatureEngine.php

# Fix 9: KSP_Components.php line 479
echo "Fixing KSP_Components.php..."
sed -i '479s/\?\?/\?/' /opt/lampp/htdocs/maruba/App/src/KSP_Components.php

echo "✅ PHP Syntax Errors Fixed!"

# Verify fixes
echo "🔍 Verifying fixes..."
ERROR_COUNT=0

# Check each file
if php -l /opt/lampp/htdocs/maruba/App/src/Views/accounting/trial_balance.php > /dev/null 2>&1; then
    echo "✅ trial_balance.php - Fixed"
else
    echo "❌ trial_balance.php - Still has errors"
    ((ERROR_COUNT++))
fi

if php -l /opt/lampp/htdocs/maruba/App/src/Views/accounting/chart_of_accounts.php > /dev/null 2>&1; then
    echo "✅ chart_of_accounts.php - Fixed"
else
    echo "❌ chart_of_accounts.php - Still has errors"
    ((ERROR_COUNT++))
fi

if php -l /opt/lampp/htdocs/maruba/App/src/AI/AICreditScoringEngine.php > /dev/null 2>&1; then
    echo "✅ AICreditScoringEngine.php - Fixed"
else
    echo "❌ AICreditScoringEngine.php - Still has errors"
    ((ERROR_COUNT++))
fi

if php -l /opt/lampp/htdocs/maruba/App/src/Caching/CacheManager.php > /dev/null 2>&1; then
    echo "✅ CacheManager.php - Fixed"
else
    echo "❌ CacheManager.php - Still has errors"
    ((ERROR_COUNT++))
fi

if php -l /opt/lampp/htdocs/maruba/App/src/OCR/OCRDocumentProcessor.php > /dev/null 2>&1; then
    echo "✅ OCRDocumentProcessor.php - Fixed"
else
    echo "❌ OCRDocumentProcessor.php - Still has errors"
    ((ERROR_COUNT++))
fi

if php -l /opt/lampp/htdocs/maruba/App/src/Database/Security/RLSPolicyManager.php > /dev/null 2>&1; then
    echo "✅ RLSPolicyManager.php - Fixed"
else
    echo "❌ RLSPolicyManager.php - Still has errors"
    ((ERROR_COUNT++))
fi

if php -l /opt/lampp/htdocs/maruba/App/src/Services/PPOBService.php > /dev/null 2>&1; then
    echo "✅ PPOBService.php - Fixed"
else
    echo "❌ PPOBService.php - Still has errors"
    ((ERROR_COUNT++))
fi

if php -l /opt/lampp/htdocs/maruba/App/src/Signature/DigitalSignatureEngine.php > /dev/null 2>&1; then
    echo "✅ DigitalSignatureEngine.php - Fixed"
else
    echo "❌ DigitalSignatureEngine.php - Still has errors"
    ((ERROR_COUNT++))
fi

if php -l /opt/lampp/htdocs/maruba/App/src/KSP_Components.php > /dev/null 2>&1; then
    echo "✅ KSP_Components.php - Fixed"
else
    echo "❌ KSP_Components.php - Still has errors"
    ((ERROR_COUNT++))
fi

echo "📊 Fix Summary:"
echo "   - Files processed: 9"
echo "   - Files fixed: $((9 - ERROR_COUNT))"
echo "   - Files with errors: $ERROR_COUNT"

if [ $ERROR_COUNT -eq 0 ]; then
    echo "🎉 All syntax errors fixed successfully!"
    exit 0
else
    echo "⚠️  Some files still have errors. Manual fixing required."
    exit 1
fi
