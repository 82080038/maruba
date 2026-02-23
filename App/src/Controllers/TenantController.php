<?php
namespace App\Controllers;

use App\Models\Tenant;
use App\Models\TenantBilling;

class TenantController
{
    public function index()
    {
        $tenantModel = new Tenant();
        $tenants = $tenantModel->getAll();
        
        include view_path('tenant/index');
    }
    
    public function create()
    {
        include view_path('tenant/create');
    }
    
    public function store()
    {
        $data = $_POST;
        
        // Validate required fields
        if (!isset($data['name']) || !isset($data['slug'])) {
            $_SESSION['error'] = 'Name and slug are required';
            header('Location: ' . route_url('tenant/create'));
            exit;
        }
        
        // Validate slug format
        if (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
            $_SESSION['error'] = 'Slug must contain only lowercase letters, numbers, and hyphens';
            header('Location: ' . route_url('tenant/create'));
            exit;
        }
        
        $tenantModel = new Tenant();
        
        try {
            $tenantId = $tenantModel->create($data);
            $_SESSION['success'] = 'Tenant created successfully';
            header('Location: ' . route_url('tenant'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to create tenant: ' . $e->getMessage();
            header('Location: ' . route_url('tenant/create'));
        }
    }
    
    public function view($id)
    {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($id);
        
        if (!$tenant) {
            $_SESSION['error'] = 'Tenant not found';
            header('Location: ' . route_url('tenant'));
            exit;
        }
        
        include view_path('tenant/view');
    }
    
    public function edit($id)
    {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($id);
        
        if (!$tenant) {
            $_SESSION['error'] = 'Tenant not found';
            header('Location: ' . route_url('tenant'));
            exit;
        }
        
        include view_path('tenant/edit');
    }
    
    public function update($id)
    {
        $data = $_POST;
        $tenantModel = new Tenant();
        
        try {
            $tenantModel->update($id, $data);
            $_SESSION['success'] = 'Tenant updated successfully';
            header('Location: ' . route_url('tenant'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update tenant: ' . $e->getMessage();
            header('Location: ' . route_url('tenant/edit/' . $id));
        }
    }
    
    public function delete($id)
    {
        $tenantModel = new Tenant();
        
        try {
            $tenantModel->delete($id);
            $_SESSION['success'] = 'Tenant deleted successfully';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to delete tenant: ' . $e->getMessage();
        }
        
        header('Location: ' . route_url('tenant'));
    }
    
    public function billing($id)
    {
        $tenantModel = new Tenant();
        $billingModel = new TenantBilling();
        
        $tenant = $tenantModel->find($id);
        $billings = $billingModel->getByTenantId($id);
        
        if (!$tenant) {
            $_SESSION['error'] = 'Tenant not found';
            header('Location: ' . route_url('tenant'));
            exit;
        }
        
        include view_path('tenant/billing');
    }
}
?>
