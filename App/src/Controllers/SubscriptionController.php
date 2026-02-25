<?php
namespace App\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantFeatureUsage;
use App\Helpers\AuthHelper;

class SubscriptionController
{
    /**
     * Show subscription plans (public)
     */
    public function plans(): void
    {
        $planModel = new SubscriptionPlan();
        $plans = $planModel->getActivePlans();
        $comparison = $planModel->getPlanComparison();

        include view_path('subscription/plans');
    }

    /**
     * Show subscription management dashboard (admin)
     */
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'view');

        $planModel = new SubscriptionPlan();
        $plans = $planModel->getActivePlans();

        $tenantModel = new Tenant();
        $tenants = $tenantModel->all(['created_at' => 'DESC']);

        // Add subscription details to tenants
        foreach ($tenants as &$tenant) {
            $tenant['subscription_details'] = $tenantModel->getSubscriptionDetails($tenant['id']);
        }

        include view_path('subscription/index');
    }

    /**
     * Show tenant subscription details
     */
    public function showTenant(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'view');

        $tenantId = (int)($_GET['tenant_id'] ?? 0);
        if (!$tenantId) {
            http_response_code(400);
            echo 'Tenant ID required';
            return;
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($tenantId);

        if (!$tenant) {
            http_response_code(404);
            echo 'Tenant not found';
            return;
        }

        $subscriptionDetails = $tenantModel->getSubscriptionDetails($tenantId);
        $usageSummary = $tenantModel->getUsageSummary($tenantId);

        include view_path('subscription/tenant_details');
    }

    /**
     * Upgrade tenant subscription
     */
    public function upgrade(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'manage');

        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $newPlan = $_POST['plan'] ?? '';
        $billingCycle = $_POST['billing_cycle'] ?? 'monthly';

        if (!$tenantId || empty($newPlan)) {
            $_SESSION['error'] = 'Tenant ID and plan are required';
            header('Location: ' . route_url('subscriptions'));
            return;
        }

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->upgradeSubscription($tenantId, $newPlan, $billingCycle);

            if ($success) {
                $_SESSION['success'] = 'Subscription upgraded successfully';

                // Send notification to tenant
                $tenant = $tenantModel->find($tenantId);
                if ($tenant && !empty($tenant['email'])) {
                    $planModel = new SubscriptionPlan();
                    $planDetails = $planModel->findByName($newPlan);

                    \App\Helpers\Notification::send(
                        'email',
                        [
                            'email' => $tenant['email'],
                            'name' => $tenant['name']
                        ],
                        'Subscription Upgraded',
                        "Your subscription has been upgraded to {$planDetails['display_name']} ({$billingCycle})."
                    );
                }
            } else {
                $_SESSION['error'] = 'Failed to upgrade subscription';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error upgrading subscription: ' . $e->getMessage();
        }

        header('Location: ' . route_url('subscription/tenant') . '?tenant_id=' . $tenantId);
    }

    /**
     * Downgrade tenant subscription
     */
    public function downgrade(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'manage');

        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $newPlan = $_POST['plan'] ?? '';
        $effectiveDate = $_POST['effective_date'] ?? null;

        if (!$tenantId || empty($newPlan)) {
            $_SESSION['error'] = 'Tenant ID and plan are required';
            header('Location: ' . route_url('subscriptions'));
            return;
        }

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->downgradeSubscription($tenantId, $newPlan, $effectiveDate);

            if ($success) {
                $_SESSION['success'] = 'Subscription downgrade scheduled successfully';
            } else {
                $_SESSION['error'] = 'Failed to schedule subscription downgrade';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error scheduling downgrade: ' . $e->getMessage();
        }

        header('Location: ' . route_url('subscription/tenant') . '?tenant_id=' . $tenantId);
    }

    /**
     * Cancel tenant subscription
     */
    public function cancel(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'manage');

        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if (!$tenantId) {
            $_SESSION['error'] = 'Tenant ID is required';
            header('Location: ' . route_url('subscriptions'));
            return;
        }

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->cancelSubscription($tenantId, $reason);

            if ($success) {
                $_SESSION['success'] = 'Subscription cancelled successfully';
            } else {
                $_SESSION['error'] = 'Failed to cancel subscription';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error cancelling subscription: ' . $e->getMessage();
        }

        header('Location: ' . route_url('subscription/tenant') . '?tenant_id=' . $tenantId);
    }

    /**
     * Reactivate tenant subscription
     */
    public function reactivate(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'manage');

        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $newPlan = $_POST['plan'] ?? null;

        if (!$tenantId) {
            $_SESSION['error'] = 'Tenant ID is required';
            header('Location: ' . route_url('subscriptions'));
            return;
        }

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->reactivateTenant($tenantId, $newPlan);

            if ($success) {
                $_SESSION['success'] = 'Tenant reactivated successfully';
            } else {
                $_SESSION['error'] = 'Failed to reactivate tenant';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error reactivating tenant: ' . $e->getMessage();
        }

        header('Location: ' . route_url('subscription/tenant') . '?tenant_id=' . $tenantId);
    }

    // ===== PLAN MANAGEMENT =====

    /**
     * Show subscription plans management
     */
    public function managePlans(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'manage_plans');

        $planModel = new SubscriptionPlan();
        $plans = $planModel->all(['created_at' => 'DESC']);

        include view_path('subscription/manage_plans');
    }

    /**
     * Create new subscription plan
     */
    public function createPlan(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'manage_plans');

        include view_path('subscription/create_plan');
    }

    /**
     * Store new subscription plan
     */
    public function storePlan(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'manage_plans');
        verify_csrf();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'display_name' => trim($_POST['display_name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price_monthly' => (float)($_POST['price_monthly'] ?? 0),
            'price_yearly' => (float)($_POST['price_yearly'] ?? 0),
            'max_users' => (int)($_POST['max_users'] ?? 5),
            'max_members' => (int)($_POST['max_members'] ?? 100),
            'max_storage_gb' => (int)($_POST['max_storage_gb'] ?? 1),
            'features' => json_encode($_POST['features'] ?? []),
            'is_active' => isset($_POST['is_active'])
        ];

        if (empty($data['name']) || empty($data['display_name'])) {
            $_SESSION['error'] = 'Plan name and display name are required';
            header('Location: ' . route_url('subscription/create-plan'));
            return;
        }

        $planModel = new SubscriptionPlan();

        // Check if plan name already exists
        $existing = $planModel->findByName($data['name']);
        if ($existing) {
            $_SESSION['error'] = 'Plan name already exists';
            header('Location: ' . route_url('subscription/create-plan'));
            return;
        }

        try {
            $planId = $planModel->create($data);
            $_SESSION['success'] = 'Subscription plan created successfully';
            header('Location: ' . route_url('subscription/manage-plans'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to create subscription plan: ' . $e->getMessage();
            header('Location: ' . route_url('subscription/create-plan'));
        }
    }

    /**
     * Edit subscription plan
     */
    public function editPlan(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'manage_plans');

        $planId = (int)($_GET['id'] ?? 0);
        if (!$planId) {
            http_response_code(400);
            echo 'Plan ID required';
            return;
        }

        $planModel = new SubscriptionPlan();
        $plan = $planModel->find($planId);

        if (!$plan) {
            http_response_code(404);
            echo 'Plan not found';
            return;
        }

        include view_path('subscription/edit_plan');
    }

    /**
     * Update subscription plan
     */
    public function updatePlan(): void
    {
        require_login();
        AuthHelper::requirePermission('subscriptions', 'manage_plans');
        verify_csrf();

        $planId = (int)($_GET['id'] ?? 0);
        if (!$planId) {
            http_response_code(400);
            echo 'Plan ID required';
            return;
        }

        $data = [
            'display_name' => trim($_POST['display_name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price_monthly' => (float)($_POST['price_monthly'] ?? 0),
            'price_yearly' => (float)($_POST['price_yearly'] ?? 0),
            'max_users' => (int)($_POST['max_users'] ?? 5),
            'max_members' => (int)($_POST['max_members'] ?? 100),
            'max_storage_gb' => (int)($_POST['max_storage_gb'] ?? 1),
            'features' => json_encode($_POST['features'] ?? []),
            'is_active' => isset($_POST['is_active'])
        ];

        if (empty($data['display_name'])) {
            $_SESSION['error'] = 'Display name is required';
            header('Location: ' . route_url('subscription/edit-plan') . '?id=' . $planId);
            return;
        }

        $planModel = new SubscriptionPlan();

        try {
            $success = $planModel->update($planId, $data);

            if ($success) {
                $_SESSION['success'] = 'Subscription plan updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update subscription plan';
            }

            header('Location: ' . route_url('subscription/manage-plans'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update subscription plan: ' . $e->getMessage();
            header('Location: ' . route_url('subscription/edit-plan') . '?id=' . $planId);
        }
    }

    // ===== API ENDPOINTS =====

    /**
     * Get subscription plans API
     */
    public function getPlansApi(): void
    {
        $planModel = new SubscriptionPlan();
        $plans = $planModel->getActivePlans();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'plans' => $plans]);
    }

    /**
     * Get tenant subscription details API
     */
    public function getTenantSubscriptionApi(): void
    {
        require_login();

        $tenantId = (int)($_GET['tenant_id'] ?? 0);
        if (!$tenantId) {
            http_response_code(400);
            echo json_encode(['error' => 'Tenant ID required']);
            return;
        }

        $tenantModel = new Tenant();
        $details = $tenantModel->getSubscriptionDetails($tenantId);

        if (!$details) {
            http_response_code(404);
            echo json_encode(['error' => 'Tenant not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'subscription' => $details]);
    }

    /**
     * Check feature access API
     */
    public function checkFeatureAccessApi(): void
    {
        require_login();

        $feature = $_GET['feature'] ?? '';
        if (empty($feature)) {
            http_response_code(400);
            echo json_encode(['error' => 'Feature name required']);
            return;
        }

        // Get current tenant
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo json_encode(['error' => 'No tenant context']);
            return;
        }

        $tenantModel = new Tenant();
        $access = $tenantModel->checkFeatureAccess($currentTenant['id'], $feature);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'access' => $access]);
    }

    /**
     * Get tenant usage summary API
     */
    public function getUsageSummaryApi(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo json_encode(['error' => 'No tenant context']);
            return;
        }

        $tenantModel = new Tenant();
        $usage = $tenantModel->getUsageSummary($currentTenant['id']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'usage' => $usage]);
    }

    // ===== UTILITY METHODS =====

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
}
