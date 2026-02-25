<?php
namespace App\Controllers;

use App\Models\CooperativeReporting;
use App\Models\Tenant;
use App\Helpers\AuthHelper;

class CooperativeReportingController
{
    /**
     * Show cooperative reports dashboard
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

        $reportingModel = new CooperativeReporting();
        $reports = $reportingModel->getByTenant($currentTenant['id']);

        include view_path('cooperative/reports/index');
    }

    /**
     * Generate monthly report
     */
    public function generateMonthlyReport(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $month = (int)($_POST['month'] ?? date('m'));
        $year = (int)($_POST['year'] ?? date('Y'));

        $reportingModel = new CooperativeReporting();
        $user = current_user();

        try {
            $reportId = $reportingModel->generateMonthlyReport($currentTenant['id'], $month, $year, $user['id']);
            $_SESSION['success'] = "Laporan bulanan {$month}/{$year} berhasil dibuat.";
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal membuat laporan bulanan: ' . $e->getMessage();
        }

        header('Location: ' . route_url('reports'));
    }

    /**
     * Generate annual report
     */
    public function generateAnnualReport(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $year = (int)($_POST['year'] ?? date('Y'));

        $reportingModel = new CooperativeReporting();
        $user = current_user();

        try {
            $reportId = $reportingModel->generateAnnualReport($currentTenant['id'], $year, $user['id']);
            $_SESSION['success'] = "Laporan tahunan {$year} berhasil dibuat.";
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal membuat laporan tahunan: ' . $e->getMessage();
        }

        header('Location: ' . route_url('reports'));
    }

    /**
     * View report details
     */
    public function show(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Report not found';
            return;
        }

        $reportingModel = new CooperativeReporting();
        $report = $reportingModel->findWithData($id);

        if (!$report || $report['tenant_id'] !== $currentTenant['id']) {
            http_response_code(404);
            echo 'Report not found';
            return;
        }

        include view_path('cooperative/reports/show');
    }

    /**
     * Download report as PDF
     */
    public function downloadReport(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Report not found';
            return;
        }

        $reportingModel = new CooperativeReporting();
        $report = $reportingModel->findWithData($id);

        if (!$report || $report['tenant_id'] !== $currentTenant['id']) {
            http_response_code(404);
            echo 'Report not found';
            return;
        }

        // Generate HTML content for PDF
        $html = $this->generateReportHTML($report);

        // For now, output as HTML (in production, use a PDF library)
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . $report['report_type'] . '_' . $report['report_year'] . '.html"');
        echo $html;
    }

    /**
     * Get growth analytics data
     */
    public function getGrowthAnalytics(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $reportingModel = new CooperativeReporting();
        $currentYear = date('Y');

        // Get reports for current year
        $monthlyReports = $reportingModel->getByTenant($currentTenant['id'], 'monthly_activity');

        $analytics = [
            'membership_trend' => [],
            'loan_trend' => [],
            'savings_trend' => [],
            'revenue_trend' => []
        ];

        foreach ($monthlyReports as $report) {
            if ($report['report_year'] == $currentYear) {
                $data = json_decode($report['data'] ?? '{}', true);

                $month = $report['report_period'];
                $analytics['membership_trend'][$month] = $data['members']['active_members'] ?? 0;
                $analytics['loan_trend'][$month] = $data['loans']['total_loans'] ?? 0;
                $analytics['savings_trend'][$month] = $data['savings']['total_savings_balance'] ?? 0;
                $analytics['revenue_trend'][$month] = $data['summary']['total_revenue'] ?? 0;
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'analytics' => $analytics]);
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $reportingModel = new CooperativeReporting();

        // Get latest annual report
        $annualReports = $reportingModel->getByTenant($currentTenant['id'], 'annual_report');
        $latestAnnual = !empty($annualReports) ? $annualReports[0] : null;

        $metrics = [
            'current_year' => date('Y'),
            'performance' => []
        ];

        if ($latestAnnual) {
            $data = json_decode($latestAnnual['data'] ?? '{}', true);
            $metrics['performance'] = $data['performance_metrics'] ?? [];
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'metrics' => $metrics]);
    }

    // ===== PRIVATE METHODS =====

    /**
     * Generate HTML report content
     */
    private function generateReportHTML(array $report): string
    {
        $data = $report['data'];
        $type = $report['report_type'];

        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>' . ucfirst(str_replace('_', ' ', $type)) . ' Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .company { font-size: 24px; font-weight: bold; }
                .title { font-size: 18px; margin: 10px 0; }
                .period { font-size: 14px; color: #666; }
                .section { margin: 30px 0; }
                .section-title { font-size: 16px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .summary { background-color: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .metric { display: inline-block; margin: 10px 20px 10px 0; }
                .metric-value { font-size: 24px; font-weight: bold; color: #007bff; }
                .metric-label { font-size: 12px; color: #666; text-transform: uppercase; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company">APLIKASI KSP</div>
                <div class="title">' . ucfirst(str_replace('_', ' ', $type)) . ' Report</div>
                <div class="period">';

        if ($type === 'monthly_activity') {
            $monthName = date('F', mktime(0, 0, 0, $report['report_period'], 1));
            $html .= "Periode: {$monthName} {$report['report_year']}";
        } else {
            $html .= "Tahun: {$report['report_year']}";
        }

        $html .= '</div>
            </div>';

        if ($type === 'monthly_activity') {
            $html .= $this->generateMonthlyReportHTML($data);
        } elseif ($type === 'annual_report') {
            $html .= $this->generateAnnualReportHTML($data);
        }

        $html .= '
            <div style="margin-top: 50px; text-align: center; font-size: 12px; color: #666;">
                <p>Laporan ini dihasilkan pada ' . date('d/m/Y H:i:s') . '</p>
                <p>APLIKASI KSP - Sistem Informasi Koperasi Simpan Pinjam</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Generate monthly report HTML
     */
    private function generateMonthlyReportHTML(array $data): string
    {
        $html = '<div class="summary">
            <h3>Ringkasan Bulanan</h3>
            <div class="metric">
                <div class="metric-value">' . ($data['members']['active_members'] ?? 0) . '</div>
                <div class="metric-label">Anggota Aktif</div>
            </div>
            <div class="metric">
                <div class="metric-value">' . ($data['loans']['disbursed_loans'] ?? 0) . '</div>
                <div class="metric-label">Pinjaman Disbursed</div>
            </div>
            <div class="metric">
                <div class="metric-value">Rp ' . number_format($data['savings']['total_savings_balance'] ?? 0, 0, ',', '.') . '</div>
                <div class="metric-label">Total Simpanan</div>
            </div>
            <div class="metric">
                <div class="metric-value">Rp ' . number_format($data['summary']['total_revenue'] ?? 0, 0, ',', '.') . '</div>
                <div class="metric-label">Total Pendapatan</div>
            </div>
        </div>';

        // Members section
        $html .= '<div class="section">
            <div class="section-title">Data Anggota</div>
            <table>
                <tr><th>Total Anggota</th><td>' . ($data['members']['total_members'] ?? 0) . '</td></tr>
                <tr><th>Anggota Aktif</th><td>' . ($data['members']['active_members'] ?? 0) . '</td></tr>
                <tr><th>Anggota Baru</th><td>' . ($data['members']['new_members'] ?? 0) . '</td></tr>
            </table>
        </div>';

        // Loans section
        $html .= '<div class="section">
            <div class="section-title">Data Pinjaman</div>
            <table>
                <tr><th>Total Pinjaman</th><td>' . ($data['loans']['total_loans'] ?? 0) . '</td></tr>
                <tr><th>Pinjaman Disbursed</th><td>' . ($data['loans']['disbursed_loans'] ?? 0) . '</td></tr>
                <tr><th>Pinjaman Baru</th><td>' . ($data['loans']['new_loans'] ?? 0) . '</td></tr>
                <tr><th>Total Disbursed</th><td>Rp ' . number_format($data['loans']['total_disbursed'] ?? 0, 0, ',', '.') . '</td></tr>
            </table>
        </div>';

        // Savings section
        $html .= '<div class="section">
            <div class="section-title">Data Simpanan</div>
            <table>
                <tr><th>Penyimpan Aktif</th><td>' . ($data['savings']['active_savers'] ?? 0) . '</td></tr>
                <tr><th>Rekening Pokok</th><td>' . ($data['savings']['pokok_accounts'] ?? 0) . '</td></tr>
                <tr><th>Rekening Wajib</th><td>' . ($data['savings']['wajib_accounts'] ?? 0) . '</td></tr>
                <tr><th>Rekening Sukarela</th><td>' . ($data['savings']['sukarela_accounts'] ?? 0) . '</td></tr>
                <tr><th>Total Saldo Simpanan</th><td>Rp ' . number_format($data['savings']['total_savings_balance'] ?? 0, 0, ',', '.') . '</td></tr>
            </table>
        </div>';

        return $html;
    }

    /**
     * Generate annual report HTML
     */
    private function generateAnnualReportHTML(array $data): string
    {
        $summary = $data['annual_summary'] ?? [];

        $html = '<div class="summary">
            <h3>Ringkasan Tahunan ' . $data['year'] . '</h3>
            <div class="metric">
                <div class="metric-value">' . ($summary['total_members'] ?? 0) . '</div>
                <div class="metric-label">Total Anggota</div>
            </div>
            <div class="metric">
                <div class="metric-value">' . ($summary['new_members'] ?? 0) . '</div>
                <div class="metric-label">Anggota Baru</div>
            </div>
            <div class="metric">
                <div class="metric-value">' . ($summary['total_loans'] ?? 0) . '</div>
                <div class="metric-label">Total Pinjaman</div>
            </div>
            <div class="metric">
                <div class="metric-value">Rp ' . number_format($summary['total_disbursed'] ?? 0, 0, ',', '.') . '</div>
                <div class="metric-label">Total Disbursed</div>
            </div>
        </div>';

        $html .= '<div class="section">
            <div class="section-title">Metrik Kinerja</div>
            <table>
                <tr><th>Indikator</th><th>Nilai</th></tr>';

        $performance = $data['performance_metrics'] ?? [];
        foreach ($performance as $key => $value) {
            $label = match($key) {
                'membership_growth' => 'Pertumbuhan Anggota',
                'loan_portfolio_growth' => 'Pertumbuhan Portofolio Pinjaman',
                'savings_growth' => 'Pertumbuhan Simpanan',
                'profitability' => 'Profitabilitas',
                default => ucfirst(str_replace('_', ' ', $key))
            };

            $formattedValue = is_numeric($value) ? number_format($value, 2) . '%' : $value;
            $html .= "<tr><td>{$label}</td><td>{$formattedValue}</td></tr>";
        }

        $html .= '</table></div>';

        return $html;
    }

    /**
     * Get current tenant from session or subdomain
     */
    private function getCurrentTenant(): ?array
    {
        // Check if we're in a tenant context via middleware
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
