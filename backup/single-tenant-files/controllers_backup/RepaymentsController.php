<?php
namespace App\Controllers;

use App\Models\Repayment;
use App\Models\Loan;
use App\Models\Member;
use App\Helpers\AuthHelper;
use App\Helpers\FileUpload;
use App\Helpers\Notification;

class RepaymentsController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('repayments', 'view');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $status = $_GET['status'] ?? null;

        $repaymentModel = new Repayment();
        $conditions = $status ? ['status' => $status] : [];

        $result = $repaymentModel->paginate($page, $limit, $conditions);

        // Add loan and member info to results
        foreach ($result['items'] as &$repayment) {
            $loanModel = new Loan();
            $memberModel = new Member();

            $loan = $loanModel->find($repayment['loan_id']);
            if ($loan) {
                $member = $memberModel->find($loan['member_id']);
                $repayment['loan_amount'] = $loan['amount'];
                $repayment['member_name'] = $member ? $member['name'] : 'Unknown';
                $repayment['member_phone'] = $member ? $member['phone'] : '';
            }
        }

        include view_path('repayments/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('repayments', 'create');

        // Get overdue repayments
        $repaymentModel = new Repayment();
        $overdueRepayments = $repaymentModel->getOverdueRepayments();

        // Get repayments due this week
        $dueThisWeek = $repaymentModel->getDueThisWeek();

        include view_path('repayments/create');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('repayments', 'create');
        verify_csrf();

        $repaymentId = (int)($_POST['repayment_id'] ?? 0);
        $amountPaid = (float)($_POST['amount_paid'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');

        if (!$repaymentId || $amountPaid <= 0) {
            $_SESSION['error'] = 'ID pembayaran dan jumlah bayar harus diisi.';
            header('Location: ' . route_url('repayments/create'));
            return;
        }

        $repaymentModel = new Repayment();
        $user = current_user();

        try {
            // Get repayment details
            $repayment = $repaymentModel->find($repaymentId);
            if (!$repayment) {
                $_SESSION['error'] = 'Data pembayaran tidak ditemukan.';
                header('Location: ' . route_url('repayments/create'));
                return;
            }

            // Get loan and member details
            $loanModel = new Loan();
            $memberModel = new Member();
            $loan = $loanModel->find($repayment['loan_id']);
            $member = $memberModel->find($loan['member_id']);

            // Handle payment proof upload
            $proofPath = null;
            if (!empty($_FILES['payment_proof']['name'])) {
                $uploadResult = FileUpload::upload($_FILES['payment_proof'], 'repayments/proofs/', [
                    'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
                    'max_size' => 5 * 1024 * 1024, // 5MB
                    'prefix' => 'proof_'
                ]);

                if ($uploadResult['success']) {
                    $proofPath = $uploadResult['path'];
                } else {
                    $_SESSION['error'] = 'Gagal upload bukti pembayaran: ' . $uploadResult['error'];
                    header('Location: ' . route_url('repayments/create'));
                    return;
                }
            }

            // Record payment
            $paymentData = [
                'amount_paid' => $amountPaid,
                'method' => $paymentMethod,
                'paid_date' => $paymentDate,
                'proof_path' => $proofPath,
                'notes' => $notes
            ];

            $success = $repaymentModel->recordPayment($repaymentId, $paymentData, $user['id']);

            if ($success) {
                // Send payment confirmation notification
                if ($member) {
                    Notification::send('whatsapp', $member, 'Pembayaran Angsuran Diterima', "Pembayaran angsuran sebesar Rp " . number_format($amountPaid, 0, ',', '.') . " telah diterima. Terima kasih.");
                }

                $_SESSION['success'] = 'Pembayaran berhasil dicatat.';
                header('Location: ' . route_url('repayments'));
            } else {
                $_SESSION['error'] = 'Gagal mencatat pembayaran.';
                header('Location: ' . route_url('repayments/create'));
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
            header('Location: ' . route_url('repayments/create'));
        }
    }

    public function show(): void
    {
        require_login();
        AuthHelper::requirePermission('repayments', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Repayment not found';
            return;
        }

        $repaymentModel = new Repayment();
        $repayment = $repaymentModel->find($id);

        if (!$repayment) {
            http_response_code(404);
            echo 'Repayment not found';
            return;
        }

        // Get related data
        $loanModel = new Loan();
        $memberModel = new Member();

        $loan = $loanModel->find($repayment['loan_id']);
        if ($loan) {
            $member = $memberModel->find($loan['member_id']);
            $repayment['loan'] = $loan;
            $repayment['member'] = $member;
        }

        include view_path('repayments/show');
    }

    // ===== API ENDPOINTS =====
    public function getOverdueApi(): void
    {
        require_login();

        $repaymentModel = new Repayment();
        $overdue = $repaymentModel->getOverdueRepayments();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'overdue_count' => count($overdue),
            'overdue' => $overdue
        ]);
    }

    public function getDueThisWeekApi(): void
    {
        require_login();

        $repaymentModel = new Repayment();
        $dueThisWeek = $repaymentModel->getDueThisWeek();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'due_count' => count($dueThisWeek),
            'due_this_week' => $dueThisWeek
        ]);
    }

    public function recordPaymentApi(): void
    {
        require_login();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['repayment_id'], $input['amount_paid'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Repayment ID and amount paid are required']);
            return;
        }

        $repaymentModel = new Repayment();
        $user = current_user();

        try {
            $paymentData = [
                'amount_paid' => (float)$input['amount_paid'],
                'method' => $input['payment_method'] ?? 'cash',
                'paid_date' => $input['payment_date'] ?? date('Y-m-d'),
                'notes' => $input['notes'] ?? ''
            ];

            $success = $repaymentModel->recordPayment($input['repayment_id'], $paymentData, $user['id']);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment recorded successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to record payment']);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to process payment: ' . $e->getMessage()]);
        }
    }

    // ===== REMINDER SYSTEM =====
    public function sendReminders(): void
    {
        require_login();
        AuthHelper::requirePermission('repayments', 'create');

        $repaymentModel = new Repayment();
        $overdue = $repaymentModel->getOverdueRepayments();
        $dueThisWeek = $repaymentModel->getDueThisWeek();

        $totalSent = 0;
        $errors = [];

        // Send reminders for overdue payments
        foreach ($overdue as $repayment) {
            try {
                $loanModel = new Loan();
                $memberModel = new Member();

                $loan = $loanModel->find($repayment['loan_id']);
                $member = $memberModel->find($loan['member_id']);

                if ($member && !empty($member['phone'])) {
                    $result = Notification::sendPaymentReminder($repayment, $member);
                    if ($result['whatsapp']['success'] ?? false) {
                        $totalSent++;
                    } else {
                        $errors[] = "Failed to send reminder to {$member['name']}";
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Error sending reminder: " . $e->getMessage();
            }
        }

        // Send reminders for payments due this week
        foreach ($dueThisWeek as $repayment) {
            try {
                $loanModel = new Loan();
                $memberModel = new Member();

                $loan = $loanModel->find($repayment['loan_id']);
                $member = $memberModel->find($loan['member_id']);

                if ($member && !empty($member['phone'])) {
                    $result = Notification::send('whatsapp', $member, 'Pengingat Pembayaran Angsuran', "Angsuran Anda akan jatuh tempo pada " . date('d/m/Y', strtotime($repayment['due_date'])) . ". Jumlah: Rp " . number_format($repayment['amount_due'], 0, ',', '.'));
                    if ($result['success']) {
                        $totalSent++;
                    } else {
                        $errors[] = "Failed to send reminder to {$member['name']}";
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Error sending reminder: " . $e->getMessage();
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'total_sent' => $totalSent,
            'errors' => $errors
        ]);
    }

    // ===== REPORTING =====
    public function getStats(): void
    {
        require_login();

        $repaymentModel = new Repayment();
        $stats = $repaymentModel->getStatistics();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }

    public function exportRepayments(): void
    {
        require_login();
        AuthHelper::requirePermission('repayments', 'view');

        $repaymentModel = new Repayment();
        $repayments = $repaymentModel->findWhere([], ['due_date' => 'DESC']);

        // CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="repayments_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, [
            'ID', 'Loan ID', 'Due Date', 'Paid Date', 'Amount Due', 'Amount Paid',
            'Status', 'Method', 'Member Name', 'Collector Name'
        ]);

        // CSV data
        foreach ($repayments as $repayment) {
            // Get additional data
            $loanModel = new Loan();
            $memberModel = new Member();
            $userModel = new \App\Models\User();

            $loan = $loanModel->find($repayment['loan_id']);
            $member = $loan ? $memberModel->find($loan['member_id']) : null;
            $collector = $repayment['collector_id'] ? $userModel->find($repayment['collector_id']) : null;

            fputcsv($output, [
                $repayment['id'],
                $repayment['loan_id'],
                $repayment['due_date'],
                $repayment['paid_date'] ?? '',
                $repayment['amount_due'],
                $repayment['amount_paid'] ?? 0,
                $repayment['status'],
                $repayment['method'] ?? '',
                $member ? $member['name'] : 'Unknown',
                $collector ? $collector['name'] : 'Unknown'
            ]);
        }

        fclose($output);
        exit;
    }
}
