<?php
namespace App\Controllers;

use App\Models\Loan;
use App\Models\Member;
use App\Models\Product;
use App\Models\Repayment;
use App\Models\Survey;
use App\Models\AuditLog;
use App\Helpers\AuthHelper;

class ReportsController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('reports', 'view');

        // Get comprehensive statistics
        $loanModel = new Loan();
        $memberModel = new Member();
        $repaymentModel = new Repayment();
        $surveyModel = new Survey();

        $loanStats = $loanModel->getStatistics();
        $memberStats = $memberModel->getStatistics();
        $repaymentStats = $repaymentModel->getStatistics();
        $surveyStats = $surveyModel->getStatistics();

        // Monthly trends (last 12 months)
        $monthlyTrends = $this->getMonthlyTrends();

        // Top performing products
        $topProducts = $this->getTopProducts();

        // Regional distribution
        $regionalStats = $this->getRegionalStats();

        include view_path('reports/index');
    }

    public function loansReport(): void
    {
        require_login();
        AuthHelper::requirePermission('reports', 'view');

        $status = $_GET['status'] ?? null;
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $loanModel = new Loan();
        $conditions = [];

        if ($status) {
            $conditions['status'] = $status;
        }

        // Add date range filter
        $loans = $loanModel->findWhere($conditions, ['created_at' => 'DESC']);

        // Filter by date range
        $loans = array_filter($loans, function($loan) use ($startDate, $endDate) {
            return $loan['created_at'] >= $startDate && $loan['created_at'] <= $endDate;
        });

        include view_path('reports/loans');
    }

    public function membersReport(): void
    {
        require_login();
        AuthHelper::requirePermission('reports', 'view');

        $memberModel = new Member();
        $members = $memberModel->all(['name' => 'ASC']);

        // Add loan summary for each member
        foreach ($members as &$member) {
            $loanModel = new Loan();
            $memberLoans = $loanModel->findWhere(['member_id' => $member['id']]);

            $member['total_loans'] = count($memberLoans);
            $member['active_loans'] = count(array_filter($memberLoans, fn($loan) => in_array($loan['status'], ['approved', 'disbursed'])));
            $member['total_outstanding'] = array_sum(array_column(
                array_filter($memberLoans, fn($loan) => in_array($loan['status'], ['approved', 'disbursed'])),
                'amount'
            ));
        }

        include view_path('reports/members');
    }

    public function financialReport(): void
    {
        require_login();
        AuthHelper::requirePermission('reports', 'view');

        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');

        // Revenue breakdown
        $repaymentModel = new Repayment();
        $repaymentStats = $repaymentModel->getStatistics();

        // Loan portfolio analysis
        $loanModel = new Loan();
        $loanStats = $loanModel->getStatistics();

        // Product performance
        $productPerformance = $this->getProductPerformance($month, $year);

        include view_path('reports/financial');
    }

    public function export(): void
    {
        require_login();
        AuthHelper::requirePermission('reports', 'export');

        $type = $_GET['type'] ?? 'loans';
        $format = $_GET['format'] ?? 'csv';

        switch ($type) {
            case 'loans':
                $this->exportLoans($format);
                break;
            case 'members':
                $this->exportMembers($format);
                break;
            case 'repayments':
                $this->exportRepayments($format);
                break;
            default:
                http_response_code(400);
                echo 'Invalid export type';
        }
    }

    private function exportLoans(string $format): void
    {
        $loanModel = new Loan();
        $loans = $loanModel->all(['created_at' => 'DESC']);

        if ($format === 'csv') {
            $this->exportLoansCsv($loans);
        } elseif ($format === 'pdf') {
            $this->exportLoansPdf($loans);
        }
    }

    private function exportLoansCsv(array $loans): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="loans_report_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'ID', 'Member Name', 'Product', 'Amount', 'Tenor', 'Rate', 'Status', 'Created Date'
        ]);

        foreach ($loans as $loan) {
            $memberModel = new Member();
            $productModel = new Product();

            $member = $memberModel->find($loan['member_id']);
            $product = $productModel->find($loan['product_id']);

            fputcsv($output, [
                $loan['id'],
                $member ? $member['name'] : 'Unknown',
                $product ? $product['name'] : 'Unknown',
                $loan['amount'],
                $loan['tenor_months'],
                $loan['rate'],
                $loan['status'],
                $loan['created_at']
            ]);
        }

        fclose($output);
    }

    private function exportLoansPdf(array $loans): void
    {
        // PDF export using a library like TCPDF or FPDF
        // For now, redirect to CSV
        header('Location: ?type=loans&format=csv');
    }

    private function exportMembers(string $format): void
    {
        $memberModel = new Member();
        $members = $memberModel->all(['name' => 'ASC']);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="members_report_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'ID', 'Name', 'NIK', 'Phone', 'Address', 'Status', 'Total Loans', 'Outstanding'
        ]);

        foreach ($members as $member) {
            $loanModel = new Loan();
            $memberLoans = $loanModel->findWhere(['member_id' => $member['id']]);
            $outstanding = array_sum(array_column(
                array_filter($memberLoans, fn($loan) => in_array($loan['status'], ['approved', 'disbursed'])),
                'amount'
            ));

            fputcsv($output, [
                $member['id'],
                $member['name'],
                $member['nik'],
                $member['phone'],
                $member['address'],
                $member['status'],
                count($memberLoans),
                $outstanding
            ]);
        }

        fclose($output);
    }

    private function exportRepayments(string $format): void
    {
        $repaymentModel = new Repayment();
        $repayments = $repaymentModel->all(['due_date' => 'DESC']);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="repayments_report_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'ID', 'Member Name', 'Due Date', 'Amount Due', 'Amount Paid', 'Status', 'Method'
        ]);

        foreach ($repayments as $repayment) {
            $loanModel = new Loan();
            $memberModel = new Member();

            $loan = $loanModel->find($repayment['loan_id']);
            $member = $loan ? $memberModel->find($loan['member_id']) : null;

            fputcsv($output, [
                $repayment['id'],
                $member ? $member['name'] : 'Unknown',
                $repayment['due_date'],
                $repayment['amount_due'],
                $repayment['amount_paid'] ?? 0,
                $repayment['status'],
                $repayment['method'] ?? ''
            ]);
        }

        fclose($output);
    }

    // ===== API ENDPOINTS =====
    public function getDashboardStatsApi(): void
    {
        require_login();

        $loanModel = new Loan();
        $memberModel = new Member();
        $repaymentModel = new Repayment();

        $stats = [
            'outstanding' => $loanModel->count(['status' => ['approved', 'disbursed']]),
            'active_members' => $memberModel->count(['status' => 'active']),
            'running_loans' => $loanModel->count(['status' => ['draft', 'survey', 'review', 'approved', 'disbursed']]),
            'npl_ratio' => $loanModel->getNPLRatio(),
            'repayment_stats' => $repaymentModel->getStatistics()
        ];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'stats' => $stats]);
    }

    public function getMonthlyTrends(): array
    {
        $trends = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-{$i} months"));
            $startDate = $date . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            // Count loans created in this month
            $loanModel = new Loan();
            $monthlyLoans = count(array_filter($loanModel->all(), function($loan) use ($startDate, $endDate) {
                return $loan['created_at'] >= $startDate && $loan['created_at'] <= $endDate;
            }));

            // Count repayments in this month
            $repaymentModel = new Repayment();
            $monthlyRepayments = count(array_filter($repaymentModel->all(), function($repayment) use ($startDate, $endDate) {
                return $repayment['paid_date'] && $repayment['paid_date'] >= $startDate && $repayment['paid_date'] <= $endDate;
            }));

            $trends[] = [
                'month' => date('M Y', strtotime($startDate)),
                'loans' => $monthlyLoans,
                'repayments' => $monthlyRepayments
            ];
        }

        return $trends;
    }

    private function getTopProducts(): array
    {
        $loanModel = new Loan();
        $productModel = new Product();

        $loans = $loanModel->findWhere(['status' => ['approved', 'disbursed']]);
        $productCounts = [];

        foreach ($loans as $loan) {
            $productId = $loan['product_id'];
            if (!isset($productCounts[$productId])) {
                $productCounts[$productId] = [
                    'count' => 0,
                    'total_amount' => 0,
                    'product' => null
                ];
            }
            $productCounts[$productId]['count']++;
            $productCounts[$productId]['total_amount'] += $loan['amount'];
        }

        // Get product details and sort by count
        foreach ($productCounts as $productId => &$data) {
            $data['product'] = $productModel->find($productId);
        }

        usort($productCounts, fn($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($productCounts, 0, 5);
    }

    private function getRegionalStats(): array
    {
        $memberModel = new Member();
        $members = $memberModel->findWhere(['status' => 'active']);

        $regions = [];
        foreach ($members as $member) {
            $address = $member['address'] ?? '';
            // Simple region extraction from address (can be improved)
            $region = 'Unknown';
            if (stripos($address, 'jakarta') !== false) $region = 'Jakarta';
            elseif (stripos($address, 'surabaya') !== false) $region = 'Surabaya';
            elseif (stripos($address, 'bandung') !== false) $region = 'Bandung';
            elseif (stripos($address, 'medan') !== false) $region = 'Medan';
            elseif (stripos($address, 'semarang') !== false) $region = 'Semarang';
            elseif (stripos($address, 'makassar') !== false) $region = 'Makassar';
            elseif (stripos($address, 'palembang') !== false) $region = 'Palembang';
            elseif (stripos($address, 'tangerang') !== false) $region = 'Tangerang';
            elseif (stripos($address, 'depok') !== false) $region = 'Depok';
            elseif (stripos($address, 'bekasi') !== false) $region = 'Bekasi';

            if (!isset($regions[$region])) {
                $regions[$region] = 0;
            }
            $regions[$region]++;
        }

        arsort($regions);
        return array_slice($regions, 0, 10, true);
    }

    private function getProductPerformance(string $month, string $year): array
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $loanModel = new Loan();
        $productModel = new Product();

        $loans = array_filter($loanModel->all(), function($loan) use ($startDate, $endDate) {
            return $loan['created_at'] >= $startDate && $loan['created_at'] <= $endDate;
        });

        $performance = [];
        foreach ($loans as $loan) {
            $productId = $loan['product_id'];
            if (!isset($performance[$productId])) {
                $performance[$productId] = [
                    'product' => $productModel->find($productId),
                    'loan_count' => 0,
                    'total_amount' => 0
                ];
            }
            $performance[$productId]['loan_count']++;
            $performance[$productId]['total_amount'] += $loan['amount'];
        }

        usort($performance, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);

        return array_slice($performance, 0, 10);
    }
}
