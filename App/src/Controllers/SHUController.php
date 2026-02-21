<?php
namespace App\Controllers;

use App\Models\SHUCalculation;
use App\Models\SHUDistribution;
use App\Models\Member;
use App\Helpers\AuthHelper;

class SHUController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('shu', 'view');

        $shuModel = new SHUCalculation();
        $calculations = $shuModel->all(['period_year' => 'DESC']);

        include view_path('shu/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('shu', 'create');

        // Get current year
        $currentYear = date('Y');

        // Check if SHU already calculated for current year
        $shuModel = new SHUCalculation();
        $existing = $shuModel->findWhere(['period_year' => $currentYear]);

        if (!empty($existing)) {
            $_SESSION['error'] = 'SHU untuk tahun ' . $currentYear . ' sudah dihitung.';
            header('Location: ' . route_url('shu'));
            exit;
        }

        include view_path('shu/create');
    }

    public function calculate(): void
    {
        require_login();
        AuthHelper::requirePermission('shu', 'create');

        $year = (int)($_POST['year'] ?? date('Y'));
        $percentage = (float)($_POST['percentage'] ?? 30.0);

        if ($percentage <= 0 || $percentage > 100) {
            $_SESSION['error'] = 'Persentase SHU harus antara 1-100%.';
            header('Location: ' . route_url('shu/create'));
            exit;
        }

        $shuModel = new SHUCalculation();

        try {
            $calculationId = $shuModel->calculateSHU($year, $percentage);
            $_SESSION['success'] = 'SHU untuk tahun ' . $year . ' berhasil dihitung.';
            header('Location: ' . route_url('shu/show') . '?id=' . $calculationId);
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menghitung SHU: ' . $e->getMessage();
            header('Location: ' . route_url('shu/create'));
        }
    }

    public function show(): void
    {
        require_login();
        AuthHelper::requirePermission('shu', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'SHU calculation not found';
            return;
        }

        $shuModel = new SHUCalculation();
        $calculation = $shuModel->findWithDistributions($id);

        if (!$calculation) {
            http_response_code(404);
            echo 'SHU calculation not found';
            return;
        }

        include view_path('shu/show');
    }

    public function approve(): void
    {
        require_login();
        AuthHelper::requirePermission('shu', 'approve');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'SHU calculation ID required';
            return;
        }

        $shuModel = new SHUCalculation();
        $user = current_user();

        $success = $shuModel->approveCalculation($id, $user['id']);

        if ($success) {
            $_SESSION['success'] = 'SHU calculation berhasil disetujui.';
        } else {
            $_SESSION['error'] = 'Gagal menyetujui SHU calculation.';
        }

        header('Location: ' . route_url('shu/show') . '?id=' . $id);
    }

    public function distribute(): void
    {
        require_login();
        AuthHelper::requirePermission('shu', 'distribute');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'SHU calculation ID required';
            return;
        }

        $shuModel = new SHUCalculation();
        $user = current_user();

        try {
            $success = $shuModel->distributeSHU($id, $user['id']);

            if ($success) {
                $_SESSION['success'] = 'SHU berhasil didistribusikan ke semua anggota.';
            } else {
                $_SESSION['error'] = 'Gagal mendistribusikan SHU.';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error distributing SHU: ' . $e->getMessage();
        }

        header('Location: ' . route_url('shu/show') . '?id=' . $id);
    }

    // ===== API ENDPOINTS =====
    public function getSHUStatsApi(): void
    {
        require_login();

        $shuModel = new SHUCalculation();
        $distributionModel = new SHUDistribution();

        $stats = [
            'calculations' => $shuModel->getStatistics(),
            'distributions' => $distributionModel->getStatistics()
        ];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'stats' => $stats]);
    }

    public function getMemberSHUApi(): void
    {
        require_login();

        $memberId = (int)($_GET['member_id'] ?? 0);
        if (!$memberId) {
            http_response_code(400);
            echo json_encode(['error' => 'Member ID required']);
            return;
        }

        $distributionModel = new SHUDistribution();
        $distributions = $distributionModel->getByMemberId($memberId);
        $totalSHU = $distributionModel->getTotalSHUByMember($memberId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'distributions' => $distributions,
            'total_shu' => $totalSHU
        ]);
    }

    // ===== REPORTING =====
    public function exportSHU(): void
    {
        require_login();
        AuthHelper::requirePermission('shu', 'view');

        $year = (int)($_GET['year'] ?? date('Y'));

        $distributionModel = new SHUDistribution();
        $distributions = $distributionModel->getYearlySummary($year);

        // CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="shu_' . $year . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Member Name', 'NIK', 'Savings Balance', 'Loan Balance', 'SHU Amount', 'Status', 'Distributed At'
        ]);

        foreach ($distributions as $distribution) {
            fputcsv($output, [
                $distribution['member_name'],
                $distribution['nik'],
                $distribution['savings_balance'],
                $distribution['loan_balance'],
                $distribution['shu_amount'],
                $distribution['status'],
                $distribution['distributed_at'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    public function printSHUCertificate(): void
    {
        require_login();

        $calculationId = (int)($_GET['calculation_id'] ?? 0);
        $memberId = (int)($_GET['member_id'] ?? 0);

        if (!$calculationId || !$memberId) {
            http_response_code(400);
            echo 'Calculation ID and Member ID required';
            return;
        }

        // Get SHU data
        $shuModel = new SHUCalculation();
        $distributionModel = new SHUDistribution();
        $memberModel = new Member();

        $calculation = $shuModel->find($calculationId);
        $distribution = $distributionModel->findWhere([
            'shu_calculation_id' => $calculationId,
            'member_id' => $memberId
        ]);
        $member = $memberModel->find($memberId);

        if (!$calculation || empty($distribution) || !$member) {
            http_response_code(404);
            echo 'SHU data not found';
            return;
        }

        $distribution = $distribution[0];

        // Generate simple certificate HTML
        echo '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Sertifikat SHU - ' . htmlspecialchars($member['name']) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .certificate { border: 2px solid #333; padding: 40px; max-width: 600px; margin: 0 auto; text-align: center; }
                .header { font-size: 24px; font-weight: bold; margin-bottom: 30px; }
                .content { font-size: 16px; line-height: 1.6; margin-bottom: 30px; }
                .amount { font-size: 20px; font-weight: bold; color: #2563eb; margin: 20px 0; }
                .footer { font-size: 12px; color: #666; margin-top: 40px; }
            </style>
        </head>
        <body>
            <div class="certificate">
                <div class="header">SERTIFIKAT SISA HASIL USAHA</div>
                <div class="content">
                    <p>Diberikan kepada:</p>
                    <p><strong>' . htmlspecialchars($member['name']) . '</strong></p>
                    <p>NIK: ' . htmlspecialchars($member['nik']) . '</p>
                    <p>Untuk periode tahun ' . $calculation['period_year'] . '</p>
                </div>
                <div class="amount">
                    Jumlah SHU: Rp ' . number_format($distribution['shu_amount'], 0, ',', '.') . '
                </div>
                <div class="content">
                    <p>Berdasarkan kontribusi simpanan dan pinjaman anggota</p>
                    <p>Telah disetujui dan didistribusikan</p>
                </div>
                <div class="footer">
                    APLIKASI KSP - ' . date('d/m/Y') . '
                </div>
            </div>
        </body>
        </html>
        ';
        exit;
    }
}
