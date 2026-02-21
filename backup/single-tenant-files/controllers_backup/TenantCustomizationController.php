<?php
namespace App\Controllers;

use App\Models\Tenant;
use App\Helpers\AuthHelper;
use App\Helpers\FileUpload;

class TenantCustomizationController
{
    /**
     * Show tenant customization dashboard
     */
    public function index(): void
    {
        // Get current tenant
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied - No tenant found';
            return;
        }

        $tenantModel = new Tenant();
        $customization = $tenantModel->getCustomizationSettings($currentTenant['id']);

        include view_path('tenant/customization/index');
    }

    /**
     * Update tenant theme settings
     */
    public function updateTheme(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $themeSettings = [
            'theme' => $_POST['theme'] ?? 'light',
            'primary_color' => $_POST['primary_color'] ?? 'primary',
            'navbar_bg' => $_POST['navbar_bg'] ?? '#ffffff',
            'sidebar_bg' => $_POST['sidebar_bg'] ?? '#f8f9fa',
            'accent_color' => $_POST['accent_color'] ?? '#007bff',
            'font_family' => $_POST['font_family'] ?? 'Inter, sans-serif',
            'border_radius' => $_POST['border_radius'] ?? '0.375rem'
        ];

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->updateTheme($currentTenant['id'], $themeSettings);

            if ($success) {
                $_SESSION['success'] = 'Theme settings updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update theme settings';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error updating theme: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/customization'));
    }

    /**
     * Update tenant branding settings
     */
    public function updateBranding(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $brandingSettings = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'tagline' => trim($_POST['tagline'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'social_media' => [
                'facebook' => trim($_POST['facebook'] ?? ''),
                'instagram' => trim($_POST['instagram'] ?? ''),
                'twitter' => trim($_POST['twitter'] ?? ''),
                'linkedin' => trim($_POST['linkedin'] ?? ''),
                'youtube' => trim($_POST['youtube'] ?? '')
            ],
            'business_hours' => [
                'monday' => ['open' => $_POST['mon_open'] ?? '08:00', 'close' => $_POST['mon_close'] ?? '17:00', 'closed' => isset($_POST['mon_closed'])],
                'tuesday' => ['open' => $_POST['tue_open'] ?? '08:00', 'close' => $_POST['tue_close'] ?? '17:00', 'closed' => isset($_POST['tue_closed'])],
                'wednesday' => ['open' => $_POST['wed_open'] ?? '08:00', 'close' => $_POST['wed_close'] ?? '17:00', 'closed' => isset($_POST['wed_closed'])],
                'thursday' => ['open' => $_POST['thu_open'] ?? '08:00', 'close' => $_POST['thu_close'] ?? '17:00', 'closed' => isset($_POST['thu_closed'])],
                'friday' => ['open' => $_POST['fri_open'] ?? '08:00', 'close' => $_POST['fri_close'] ?? '17:00', 'closed' => isset($_POST['fri_closed'])],
                'saturday' => ['open' => $_POST['sat_open'] ?? '08:00', 'close' => $_POST['sat_close'] ?? '17:00', 'closed' => isset($_POST['sat_closed'])],
                'sunday' => ['open' => $_POST['sun_open'] ?? '08:00', 'close' => $_POST['sun_close'] ?? '17:00', 'closed' => isset($_POST['sun_closed'])]
            ]
        ];

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->updateBranding($currentTenant['id'], $brandingSettings);

            if ($success) {
                $_SESSION['success'] = 'Branding settings updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update branding settings';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error updating branding: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/customization'));
    }

    /**
     * Update tenant UI preferences
     */
    public function updateUIPreferences(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $preferences = [
            'sidebar_collapsed' => isset($_POST['sidebar_collapsed']),
            'compact_mode' => isset($_POST['compact_mode']),
            'show_notifications' => isset($_POST['show_notifications']),
            'language' => $_POST['language'] ?? 'id',
            'timezone' => $_POST['timezone'] ?? 'Asia/Jakarta',
            'date_format' => $_POST['date_format'] ?? 'd/m/Y',
            'currency_format' => $_POST['currency_format'] ?? 'IDR'
        ];

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->updateUIPreferences($currentTenant['id'], $preferences);

            if ($success) {
                $_SESSION['success'] = 'UI preferences updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update UI preferences';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error updating UI preferences: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/customization'));
    }

    /**
     * Upload tenant logo
     */
    public function uploadLogo(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        if (!isset($_FILES['logo'])) {
            $_SESSION['error'] = 'No logo file provided';
            header('Location: ' . route_url('tenant/customization'));
            return;
        }

        $tenantModel = new Tenant();

        try {
            $logoPath = $tenantModel->uploadLogo($currentTenant['id'], $_FILES['logo']);
            $_SESSION['success'] = 'Logo uploaded successfully';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to upload logo: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/customization'));
    }

    /**
     * Upload tenant favicon
     */
    public function uploadFavicon(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        if (!isset($_FILES['favicon'])) {
            $_SESSION['error'] = 'No favicon file provided';
            header('Location: ' . route_url('tenant/customization'));
            return;
        }

        $tenantModel = new Tenant();

        try {
            $faviconPath = $tenantModel->uploadFavicon($currentTenant['id'], $_FILES['favicon']);
            $_SESSION['success'] = 'Favicon uploaded successfully';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to upload favicon: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/customization'));
    }

    /**
     * Reset customization to defaults
     */
    public function resetCustomization(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->resetCustomization($currentTenant['id']);

            if ($success) {
                $_SESSION['success'] = 'Customization settings reset to defaults';
            } else {
                $_SESSION['error'] = 'Failed to reset customization settings';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error resetting customization: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/customization'));
    }

    /**
     * Generate and serve tenant CSS
     */
    public function getTenantCSS(): void
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $tenantModel = new Tenant();
        $css = $tenantModel->generateTenantCSS($currentTenant['id']);

        header('Content-Type: text/css');
        header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
        echo $css;
    }

    /**
     * Get tenant customization settings (API)
     */
    public function getSettingsApi(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo json_encode(['error' => 'No tenant context']);
            return;
        }

        $tenantModel = new Tenant();
        $settings = $tenantModel->getCustomizationSettings($currentTenant['id']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'settings' => $settings]);
    }

    /**
     * Preview theme changes
     */
    public function previewTheme(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $themeSettings = [
            'theme' => $_POST['theme'] ?? 'light',
            'primary_color' => $_POST['primary_color'] ?? 'primary',
            'navbar_bg' => $_POST['navbar_bg'] ?? '#ffffff',
            'sidebar_bg' => $_POST['sidebar_bg'] ?? '#f8f9fa',
            'accent_color' => $_POST['accent_color'] ?? '#007bff',
            'font_family' => $_POST['font_family'] ?? 'Inter, sans-serif',
            'border_radius' => $_POST['border_radius'] ?? '0.375rem'
        ];

        // Generate preview CSS
        $css = $this->generatePreviewCSS($themeSettings);

        header('Content-Type: text/css');
        echo $css;
    }

    // ===== PRIVATE METHODS =====

    /**
     * Get current tenant from session or subdomain
     */
    private function getCurrentTenant(): ?array
    {
        // Check if we're in tenant context via middleware
        if (isset($_SESSION['tenant'])) {
            return $_SESSION['tenant'];
        }

        // Try to get tenant from subdomain
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (preg_match('/^([a-z0-9-]+)\.' . preg_quote($_SERVER['SERVER_NAME'] ?? 'localhost', '/') . '$/', $host, $matches)) {
            $slug = $matches[1];
            $tenantModel = new Tenant();
            $tenant = $tenantModel->findBySlug($slug);
            if ($tenant && $tenant['status'] === 'active') {
                $_SESSION['tenant'] = $tenant;
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Generate preview CSS for theme changes
     */
    private function generatePreviewCSS(array $theme): string
    {
        $css = ":root {\n";

        if (!empty($theme['primary_color'])) {
            $css .= "  --bs-primary: {$theme['primary_color']};\n";
        }

        if (!empty($theme['accent_color'])) {
            $css .= "  --accent-color: {$theme['accent_color']};\n";
        }

        if (!empty($theme['navbar_bg'])) {
            $css .= "  --navbar-bg: {$theme['navbar_bg']};\n";
        }

        if (!empty($theme['sidebar_bg'])) {
            $css .= "  --sidebar-bg: {$theme['sidebar_bg']};\n";
        }

        if (!empty($theme['font_family'])) {
            $css .= "  --font-family: {$theme['font_family']};\n";
        }

        if (!empty($theme['border_radius'])) {
            $css .= "  --border-radius: {$theme['border_radius']};\n";
        }

        $css .= "}\n\n";

        // Add theme-specific styles
        if (!empty($theme['theme'])) {
            if ($theme['theme'] === 'dark') {
                $css .= ".theme-preview { background-color: #212529 !important; color: #ffffff !important; }\n";
                $css .= ".theme-preview .card { background-color: #343a40 !important; border-color: #495057 !important; }\n";
            } elseif ($theme['theme'] === 'blue') {
                $css .= ".theme-preview { --bs-primary: #0d6efd !important; --accent-color: #0d6efd !important; }\n";
            } elseif ($theme['theme'] === 'green') {
                $css .= ".theme-preview { --bs-primary: #198754 !important; --accent-color: #198754 !important; }\n";
            } elseif ($theme['theme'] === 'purple') {
                $css .= ".theme-preview { --bs-primary: #6f42c1 !important; --accent-color: #6f42c1 !important; }\n";
            } elseif ($theme['theme'] === 'orange') {
                $css .= ".theme-preview { --bs-primary: #fd7e14 !important; --accent-color: #fd7e14 !important; }\n";
            }
        }

        return $css;
    }
}
