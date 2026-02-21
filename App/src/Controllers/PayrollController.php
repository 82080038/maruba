<?php
namespace App\Controllers;

use App\Models\Payroll;
use App\Models\User;
use App\Helpers\AuthHelper;

class PayrollController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('payroll', 'view');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        $payrollModel = new Payroll();
        $conditions = [
            'period_month' => $month,
            'period_year' => $year
        ];

        $result = $payrollModel->paginate($page, $limit, $conditions);

        // Add employee information
        foreach ($result['items'] as &$payroll) {
            $userModel = new User();
            $employee = $userModel->find($payroll['employee_id']);
            $payroll['employee_name'] = $employee ? $employee['name'] : 'Unknown';
            $payroll['employee_username'] = $employee ? $employee['username'] : '';
        }

        include view_path('payroll/index');
    }

    public function generate(): void
    {
        require_login();
        AuthHelper::requirePermission('payroll', 'create');

        $month = (int)($_POST['month'] ?? date('m'));
        $year = (int)($_POST['year'] ?? date('Y'));

        $payrollModel = new Payroll();

        try {
            $generated = $payrollModel->generatePayroll($month, $year);

            $message = count($generated) . ' payroll records generated successfully for ' . date('F Y', strtotime($year . '-' . $month . '-01'));
            $_SESSION['success'] = $message;

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to generate payroll: ' . $e->getMessage();
        }

        header('Location: ' . route_url('payroll') . '?month=' . $month . '&year=' . $year);
    }

    public function show(): void
    {
        require_login();
        AuthHelper::requirePermission('payroll', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Payroll not found';
            return;
        }

        $payrollModel = new Payroll();
        $payroll = $payrollModel->find($id);

        if (!$payroll) {
            http_response_code(404);
            echo 'Payroll not found';
            return;
        }

        // Add employee information
        $userModel = new User();
        $employee = $userModel->find($payroll['employee_id']);
        $payroll['employee'] = $employee;

        include view_path('payroll/show');
    }

    public function approve(): void
    {
        require_login();
        AuthHelper::requirePermission('payroll', 'approve');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Payroll ID required';
            return;
        }

        $payrollModel = new Payroll();
        $user = current_user();

        $success = $payrollModel->approvePayroll($id, $user['id']);

        if ($success) {
            $_SESSION['success'] = 'Payroll approved successfully.';
        } else {
            $_SESSION['error'] = 'Failed to approve payroll.';
        }

        header('Location: ' . route_url('payroll/show') . '?id=' . $id);
    }

    public function markPaid(): void
    {
        require_login();
        AuthHelper::requirePermission('payroll', 'edit');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Payroll ID required';
            return;
        }

        $payrollModel = new Payroll();

        $success = $payrollModel->markAsPaid($id);

        if ($success) {
            $_SESSION['success'] = 'Payroll marked as paid.';

            // Send notification to employee
            $payroll = $payrollModel->find($id);
            if ($payroll) {
                $userModel = new User();
                $employee = $userModel->find($payroll['employee_id']);

                // In a real implementation, send email notification here
                // For now, just log it
                error_log("Payroll paid notification for employee: " . $employee['name']);
            }
        } else {
            $_SESSION['error'] = 'Failed to mark payroll as paid.';
        }

        header('Location: ' . route_url('payroll/show') . '?id=' . $id);
    }

    public function salarySlip(): void
    {
        require_login();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Payroll not found';
            return;
        }

        $payrollModel = new Payroll();
        $payroll = $payrollModel->find($id);

        if (!$payroll) {
            http_response_code(404);
            echo 'Payroll not found';
            return;
        }

        // Check if user can view this payroll
        $currentUser = current_user();
        if (!AuthHelper::hasPermission('payroll', 'view') && $payroll['employee_id'] !== $currentUser['id']) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        // Generate and output salary slip
        $slipHtml = $payrollModel->generateSalarySlip($id);
        echo $slipHtml;
        exit;
    }

    public function myPayroll(): void
    {
        require_login();

        $user = current_user();
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        $payrollModel = new Payroll();
        $payrolls = $payrollModel->getByEmployee($user['id']);

        // Filter by month/year if specified
        if ($month && $year) {
            $payrolls = array_filter($payrolls, function($payroll) use ($month, $year) {
                return $payroll['period_month'] == $month && $payroll['period_year'] == $year;
            });
        }

        include view_path('payroll/my_payroll');
    }

    // ===== API ENDPOINTS =====
    public function getPayrollStatsApi(): void
    {
        require_login();

        $payrollModel = new Payroll();
        $stats = $payrollModel->getStatistics();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'stats' => $stats]);
    }

    public function getEmployeePayrollApi(): void
    {
        require_login();

        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        // Check permissions
        $currentUser = current_user();
        if (!AuthHelper::hasPermission('payroll', 'view') && $employeeId !== $currentUser['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        $payrollModel = new Payroll();
        $payrolls = $payrollModel->getByEmployee($employeeId);

        // Filter by period
        $filteredPayrolls = array_filter($payrolls, function($payroll) use ($month, $year) {
            return $payroll['period_month'] == $month && $payroll['period_year'] == $year;
        });

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'payrolls' => array_values($filteredPayrolls)]);
    }

    // ===== REPORTING =====
    public function exportPayroll(): void
    {
        require_login();
        AuthHelper::requirePermission('payroll', 'view');

        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        $payrollModel = new Payroll();
        $payrolls = $payrollModel->findWhere([
            'period_month' => $month,
            'period_year' => $year
        ]);

        // CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="payroll_' . $month . '_' . $year . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Employee Name', 'Basic Salary', 'Allowances', 'Deductions', 'Net Salary', 'Status'
        ]);

        foreach ($payrolls as $payroll) {
            $userModel = new User();
            $employee = $userModel->find($payroll['employee_id']);

            fputcsv($output, [
                $employee ? $employee['name'] : 'Unknown',
                $payroll['basic_salary'],
                $payroll['allowances'],
                $payroll['deductions'],
                $payroll['net_salary'],
                $payroll['status']
            ]);
        }

        fclose($output);
        exit;
    }

    public function bulkApprove(): void
    {
        require_login();
        AuthHelper::requirePermission('payroll', 'approve');

        $month = (int)($_POST['month'] ?? 0);
        $year = (int)($_POST['year'] ?? 0);

        if (!$month || !$year) {
            $_SESSION['error'] = 'Month and year are required.';
            header('Location: ' . route_url('payroll'));
            return;
        }

        $payrollModel = new Payroll();
        $user = current_user();

        $payrolls = $payrollModel->findWhere([
            'period_month' => $month,
            'period_year' => $year,
            'status' => 'draft'
        ]);

        $approvedCount = 0;
        foreach ($payrolls as $payroll) {
            if ($payrollModel->approvePayroll($payroll['id'], $user['id'])) {
                $approvedCount++;
            }
        }

        $_SESSION['success'] = $approvedCount . ' payroll records approved successfully.';
        header('Location: ' . route_url('payroll') . '?month=' . $month . '&year=' . $year);
    }
}
