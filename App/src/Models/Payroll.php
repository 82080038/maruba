<?php
namespace App\Models;

class Payroll extends Model
{
    protected string $table = 'payroll_records';
    protected array $fillable = [
        'employee_id', 'period_month', 'period_year', 'basic_salary',
        'allowances', 'deductions', 'net_salary', 'status', 'approved_by', 'paid_at'
    ];
    protected array $casts = [
        'employee_id' => 'int',
        'period_month' => 'int',
        'period_year' => 'int',
        'basic_salary' => 'float',
        'allowances' => 'float',
        'deductions' => 'float',
        'net_salary' => 'float',
        'approved_by' => 'int',
        'paid_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    /**
     * Generate payroll for all employees
     */
    public function generatePayroll(int $month, int $year): array
    {
        $userModel = new \App\Models\User();
        $employees = $userModel->findWhere(['status' => 'active']);

        $generated = [];

        foreach ($employees as $employee) {
            // Check if payroll already exists for this period
            $existing = $this->findWhere([
                'employee_id' => $employee['id'],
                'period_month' => $month,
                'period_year' => $year
            ]);

            if (empty($existing)) {
                $payrollData = $this->calculateEmployeePayroll($employee, $month, $year);

                if ($payrollData) {
                    $payrollId = $this->create($payrollData);
                    $generated[] = [
                        'employee_id' => $employee['id'],
                        'employee_name' => $employee['name'],
                        'payroll_id' => $payrollId,
                        'net_salary' => $payrollData['net_salary']
                    ];
                }
            }
        }

        return $generated;
    }

    /**
     * Calculate payroll for an employee
     */
    private function calculateEmployeePayroll(array $employee, int $month, int $year): ?array
    {
        // Get basic salary from role or user profile (simplified)
        $basicSalary = $this->getEmployeeBasicSalary($employee);

        if ($basicSalary <= 0) {
            return null; // Skip employees without salary
        }

        // Calculate allowances (simplified)
        $allowances = $this->calculateAllowances($employee, $month, $year);

        // Calculate deductions (simplified)
        $deductions = $this->calculateDeductions($employee, $month, $year);

        // Calculate net salary
        $netSalary = $basicSalary + $allowances - $deductions;

        return [
            'employee_id' => $employee['id'],
            'period_month' => $month,
            'period_year' => $year,
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'net_salary' => $netSalary,
            'status' => 'draft'
        ];
    }

    /**
     * Get employee basic salary (simplified)
     */
    private function getEmployeeBasicSalary(array $employee): float
    {
        // In a real implementation, this would come from employee profile
        // For now, use role-based salary
        $roleSalaries = [
            'admin' => 5000000,
            'manajer' => 4500000,
            'akuntansi' => 4000000,
            'kasir' => 3500000,
            'teller' => 3200000,
            'surveyor' => 3500000,
            'collector' => 3300000,
            'staf_lapangan' => 3000000
        ];

        $roleModel = new \App\Models\Role();
        $role = $roleModel->find($employee['role_id']);

        if ($role) {
            return $roleSalaries[$role['name']] ?? 3000000;
        }

        return 3000000; // Default salary
    }

    /**
     * Calculate allowances
     */
    private function calculateAllowances(array $employee, int $month, int $year): float
    {
        $allowances = 0;

        // Transport allowance
        $allowances += 500000;

        // Meal allowance
        $allowances += 600000;

        // Communication allowance
        $allowances += 300000;

        // Performance bonus (simplified)
        $allowances += 500000;

        return $allowances;
    }

    /**
     * Calculate deductions
     */
    private function calculateDeductions(array $employee, int $month, int $year): float
    {
        $deductions = 0;

        // BPJS Kesehatan (4.5% of basic salary)
        $basicSalary = $this->getEmployeeBasicSalary($employee);
        $deductions += $basicSalary * 0.045;

        // BPJS Ketenagakerjaan (2% of basic salary)
        $deductions += $basicSalary * 0.02;

        // Income tax (simplified PPh 21)
        $deductions += $this->calculateIncomeTax($basicSalary);

        // Loan deductions (if any)
        $deductions += $this->getLoanDeductions($employee['id']);

        return $deductions;
    }

    /**
     * Calculate income tax (simplified)
     */
    private function calculateIncomeTax(float $annualSalary): float
    {
        $monthlySalary = $annualSalary / 12;

        // Simplified PPh 21 calculation
        if ($monthlySalary <= 4500000) {
            return 0;
        } elseif ($monthlySalary <= 50000000) {
            return ($monthlySalary - 4500000) * 0.05;
        } elseif ($monthlySalary <= 250000000) {
            return (5000000 * 0.05) + (($monthlySalary - 50000000) * 0.15);
        } else {
            return (5000000 * 0.05) + (200000000 * 0.15) + (($monthlySalary - 250000000) * 0.25);
        }
    }

    /**
     * Get loan deductions for employee
     */
    private function getLoanDeductions(int $employeeId): float
    {
        // Check if employee has outstanding loans
        $loanModel = new \App\Models\Loan();
        $repaymentModel = new \App\Models\Repayment();

        $loans = $loanModel->findWhere(['member_id' => $employeeId, 'status' => ['approved', 'disbursed']]);

        $totalDeductions = 0;

        foreach ($loans as $loan) {
            // Get current month repayment
            $currentMonth = date('m');
            $currentYear = date('Y');

            $repayments = $repaymentModel->findWhere([
                'loan_id' => $loan['id'],
                'due_date' => [$currentYear . '-' . $currentMonth . '-01', $currentYear . '-' . $currentMonth . '-31']
            ]);

            foreach ($repayments as $repayment) {
                if ($repayment['status'] === 'due') {
                    $totalDeductions += $repayment['amount_due'] - $repayment['amount_paid'];
                }
            }
        }

        return $totalDeductions;
    }

    /**
     * Approve payroll
     */
    public function approvePayroll(int $payrollId, int $approvedBy): bool
    {
        $payroll = $this->find($payrollId);
        if (!$payroll || $payroll['status'] !== 'draft') {
            return false;
        }

        return $this->update($payrollId, [
            'status' => 'approved',
            'approved_by' => $approvedBy
        ]);
    }

    /**
     * Mark payroll as paid
     */
    public function markAsPaid(int $payrollId): bool
    {
        $payroll = $this->find($payrollId);
        if (!$payroll || $payroll['status'] !== 'approved') {
            return false;
        }

        return $this->update($payrollId, [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get payroll by employee
     */
    public function getByEmployee(int $employeeId): array
    {
        return $this->findWhere(['employee_id' => $employeeId], ['period_year' => 'DESC', 'period_month' => 'DESC']);
    }

    /**
     * Get payroll statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_payrolls,
                SUM(net_salary) as total_salary_paid,
                AVG(net_salary) as avg_salary,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Generate salary slip
     */
    public function generateSalarySlip(int $payrollId): string
    {
        $payroll = $this->find($payrollId);
        if (!$payroll) {
            throw new \Exception('Payroll not found');
        }

        $userModel = new \App\Models\User();
        $employee = $userModel->find($payroll['employee_id']);

        if (!$employee) {
            throw new \Exception('Employee not found');
        }

        $roleModel = new \App\Models\Role();
        $role = $roleModel->find($employee['role_id']);

        // Generate HTML salary slip
        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Slip Gaji - ' . htmlspecialchars($employee['name']) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
                .company { font-size: 18px; font-weight: bold; }
                .title { font-size: 16px; margin: 10px 0; }
                .employee-info { margin-bottom: 20px; }
                .salary-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .salary-table th, .salary-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .salary-table th { background-color: #f2f2f2; }
                .total { font-weight: bold; background-color: #e9ecef; }
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company">APLIKASI KSP</div>
                <div class="title">SLIP GAJI KARYAWAN</div>
                <div>Periode: ' . $payroll['period_month'] . '/' . $payroll['period_year'] . '</div>
            </div>

            <div class="employee-info">
                <strong>Nama:</strong> ' . htmlspecialchars($employee['name']) . '<br>
                <strong>Jabatan:</strong> ' . htmlspecialchars($role ? $role['name'] : 'Unknown') . '<br>
                <strong>NIK:</strong> ' . htmlspecialchars($employee['username']) . '<br>
            </div>

            <table class="salary-table">
                <tr>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                </tr>
                <tr>
                    <td>Gaji Pokok</td>
                    <td>Rp ' . number_format($payroll['basic_salary'], 0, ',', '.') . '</td>
                </tr>
                <tr>
                    <td>Tunjangan</td>
                    <td>Rp ' . number_format($payroll['allowances'], 0, ',', '.') . '</td>
                </tr>
                <tr>
                    <td>Potongan</td>
                    <td>Rp ' . number_format($payroll['deductions'], 0, ',', '.') . '</td>
                </tr>
                <tr class="total">
                    <td><strong>Take Home Pay</strong></td>
                    <td><strong>Rp ' . number_format($payroll['net_salary'], 0, ',', '.') . '</strong></td>
                </tr>
            </table>

            <div class="footer">
                <p>Dicetak pada: ' . date('d/m/Y H:i:s') . '</p>
                <p>Slip gaji ini dihasilkan secara elektronik dan sah tanpa tanda tangan</p>
            </div>
        </body>
        </html>
        ';

        return $html;
    }
}
