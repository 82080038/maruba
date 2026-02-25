<?php
namespace App\Controllers;

use App\Models\Survey;
use App\Models\Loan;
use App\Models\Member;
use App\Helpers\AuthHelper;
use App\Helpers\FileUpload;
use App\Helpers\Notification;

class SurveysController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('surveys', 'view');

        $surveyModel = new Survey();
        $surveys = $surveyModel->getCompletedSurveys();

        include view_path('surveys/index');
    }

    public function create(): void
    {
        require_login();
        AuthHelper::requirePermission('surveys', 'create');

        // Get pending surveys for current user
        $surveyModel = new Survey();
        $pendingSurveys = $surveyModel->getPendingSurveys();

        // Filter by assigned surveyor if not admin
        $user = current_user();
        if ($user['role'] !== 'admin' && $user['role'] !== 'manajer') {
            $pendingSurveys = array_filter($pendingSurveys, function($survey) use ($user) {
                return $survey['assigned_surveyor_id'] == $user['id'];
            });
        }

        include view_path('surveys/create');
    }

    public function show(): void
    {
        require_login();
        AuthHelper::requirePermission('surveys', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Survey not found';
            return;
        }

        $surveyModel = new Survey();
        $survey = $surveyModel->findWithDetails($id);

        if (!$survey) {
            http_response_code(404);
            echo 'Survey not found';
            return;
        }

        include view_path('surveys/show');
    }

    public function store(): void
    {
        require_login();
        AuthHelper::requirePermission('surveys', 'create');

        $loanId = (int)($_POST['loan_id'] ?? 0);
        $result = trim($_POST['result'] ?? '');
        $score = (int)($_POST['score'] ?? 0);
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);
        $businessType = trim($_POST['business_type'] ?? '');
        $monthlyIncome = (float)($_POST['monthly_income'] ?? 0);
        $address = trim($_POST['address'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Validation
        if (!$loanId || empty($result) || $score < 0 || $score > 100) {
            $_SESSION['error'] = 'Data tidak lengkap atau skor tidak valid (0-100).';
            header('Location: ' . route_url('surveys/create'));
            return;
        }

        // Get loan details
        $loanModel = new Loan();
        $loan = $loanModel->find($loanId);
        if (!$loan) {
            $_SESSION['error'] = 'Pinjaman tidak ditemukan.';
            header('Location: ' . route_url('surveys/create'));
            return;
        }

        // Get member details
        $memberModel = new Member();
        $member = $memberModel->find($loan['member_id']);
        if (!$member) {
            $_SESSION['error'] = 'Anggota tidak ditemukan.';
            header('Location: ' . route_url('surveys/create'));
            return;
        }

        $surveyModel = new Survey();
        $user = current_user();

        try {
            // Prepare survey data
            $surveyData = [
                'result' => $result,
                'score' => $score,
                'geo_lat' => $lat,
                'geo_lng' => $lng,
                'business_type' => $businessType,
                'monthly_income' => $monthlyIncome,
                'survey_address' => $address,
                'notes' => $notes
            ];

            // Handle photo uploads
            $photoUploads = [];
            if (!empty($_FILES['location_photos'])) {
                $uploadResults = FileUpload::uploadMultiple($_FILES['location_photos']['name'], 'surveys/photos/', [
                    'allowed_types' => ['image/jpeg', 'image/png'],
                    'max_size' => 5 * 1024 * 1024, // 5MB
                    'prefix' => 'survey_'
                ]);

                foreach ($uploadResults as $uploadResult) {
                    if ($uploadResult['success']) {
                        $photoUploads[] = $uploadResult['path'];

                        // Create thumbnail
                        FileUpload::createThumbnail($uploadResult['path'], 300, 300);
                    }
                }
            }

            if (!empty($photoUploads)) {
                $surveyData['photo_paths'] = json_encode($photoUploads);
            }

            // Submit survey
            $surveyId = $surveyModel->submitSurvey($loanId, $surveyData, $user['id']);

            // Send notification to member
            Notification::sendSurveyCompletionNotification(
                array_merge($surveyData, ['id' => $surveyId]),
                $loan,
                $member
            );

            $_SESSION['success'] = 'Survey berhasil disimpan dan status pinjaman diperbarui ke review.';
            header('Location: ' . route_url('surveys'));

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menyimpan survey: ' . $e->getMessage();
            header('Location: ' . route_url('surveys/create'));
        }
    }

    // ===== MOBILE/API ENDPOINTS =====
    public function getPendingSurveysApi(): void
    {
        require_login();

        $surveyModel = new Survey();
        $pendingSurveys = $surveyModel->getPendingSurveys();

        // Filter by assigned surveyor
        $user = current_user();
        if ($user['role'] !== 'admin' && $user['role'] !== 'manajer') {
            $pendingSurveys = array_filter($pendingSurveys, function($survey) use ($user) {
                return $survey['assigned_surveyor_id'] == $user['id'];
            });
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'surveys' => array_values($pendingSurveys)
        ]);
    }

    public function submitSurveyApi(): void
    {
        require_login();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        $loanId = (int)($input['loan_id'] ?? 0);
        $surveyData = $input['survey'] ?? [];
        $user = current_user();

        try {
            $surveyModel = new Survey();
            $surveyId = $surveyModel->submitSurvey($loanId, $surveyData, $user['id']);

            // Get loan and member details for notification
            $loanModel = new Loan();
            $memberModel = new Member();
            $loan = $loanModel->find($loanId);
            $member = $memberModel->find($loan['member_id']);

            if ($member) {
                Notification::sendSurveyCompletionNotification(
                    array_merge($surveyData, ['id' => $surveyId]),
                    $loan,
                    $member
                );
            }

            echo json_encode([
                'success' => true,
                'survey_id' => $surveyId,
                'message' => 'Survey submitted successfully'
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to submit survey: ' . $e->getMessage()]);
        }
    }

    // ===== UTILITY METHODS =====
    public function getSurveyStats(): void
    {
        require_login();

        $surveyModel = new Survey();
        $stats = $surveyModel->getStatistics();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }

    public function exportSurveys(): void
    {
        require_login();
        AuthHelper::requirePermission('surveys', 'view');

        $surveyModel = new Survey();
        $surveys = $surveyModel->getCompletedSurveys();

        // Simple CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="surveys_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, [
            'ID', 'Loan ID', 'Member Name', 'Surveyor', 'Score', 'Result',
            'Business Type', 'Monthly Income', 'Latitude', 'Longitude', 'Created At'
        ]);

        // CSV data
        foreach ($surveys as $survey) {
            fputcsv($output, [
                $survey['id'],
                $survey['loan_id'],
                $survey['member_name'] ?? '',
                $survey['surveyor_name'] ?? '',
                $survey['score'],
                substr($survey['result'], 0, 100), // Truncate result
                $survey['business_type'] ?? '',
                $survey['monthly_income'] ?? 0,
                $survey['geo_lat'],
                $survey['geo_lng'],
                $survey['created_at']
            ]);
        }

        fclose($output);
    }
}
