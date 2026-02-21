<?php
namespace App\Controllers;

use App\Models\AccountingJournal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccounts;
use App\Helpers\AuthHelper;

class AccountingController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'view');

        $journalModel = new AccountingJournal();
        $chartModel = new ChartOfAccounts();

        $recentJournals = $journalModel->findWhere([], ['transaction_date' => 'DESC'], 10);
        $accountHierarchy = $chartModel->getHierarchy();
        $stats = $journalModel->getStatistics();

        include view_path('accounting/index');
    }

    public function journals(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'view');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $status = $_GET['status'] ?? null;

        $journalModel = new AccountingJournal();
        $conditions = $status ? ['status' => $status] : [];

        $result = $journalModel->paginate($page, $limit, $conditions);

        include view_path('accounting/journals');
    }

    public function createJournal(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'create');

        $chartModel = new ChartOfAccounts();
        $accounts = $chartModel->getActiveAccounts();

        include view_path('accounting/create_journal');
    }

    public function storeJournal(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'create');
        verify_csrf();

        $data = [
            'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
            'description' => trim($_POST['description'] ?? ''),
            'reference_number' => trim($_POST['reference_number'] ?? ''),
            'status' => $_POST['status'] ?? 'draft'
        ];

        // Parse entries from form
        $entries = [];
        $accountCodes = $_POST['account_code'] ?? [];
        $debits = $_POST['debit'] ?? [];
        $credits = $_POST['credit'] ?? [];
        $descriptions = $_POST['entry_description'] ?? [];

        for ($i = 0; $i < count($accountCodes); $i++) {
            if (!empty($accountCodes[$i])) {
                $entries[] = [
                    'account_code' => $accountCodes[$i],
                    'debit' => (float)($debits[$i] ?? 0),
                    'credit' => (float)($credits[$i] ?? 0),
                    'description' => $descriptions[$i] ?? ''
                ];
            }
        }

        $journalModel = new AccountingJournal();

        try {
            $journalId = $journalModel->createJournal($data, $entries);
            $_SESSION['success'] = 'Jurnal berhasil dibuat.';
            header('Location: ' . route_url('accounting/journals'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal membuat jurnal: ' . $e->getMessage();
            header('Location: ' . route_url('accounting/create-journal'));
        }
    }

    public function showJournal(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Journal not found';
            return;
        }

        $journalModel = new AccountingJournal();
        $journal = $journalModel->findWithEntries($id);

        if (!$journal) {
            http_response_code(404);
            echo 'Journal not found';
            return;
        }

        include view_path('accounting/show_journal');
    }

    public function postJournal(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'post');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Journal ID required';
            return;
        }

        $journalModel = new AccountingJournal();
        $user = current_user();

        $success = $journalModel->postJournal($id, $user['id']);

        if ($success) {
            $_SESSION['success'] = 'Jurnal berhasil diposting.';
        } else {
            $_SESSION['error'] = 'Gagal memposting jurnal.';
        }

        header('Location: ' . route_url('accounting/show-journal') . '?id=' . $id);
    }

    public function generalLedger(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'view');

        $accountCode = $_GET['account'] ?? '';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        if (empty($accountCode)) {
            // Show account selection
            $chartModel = new ChartOfAccounts();
            $accounts = $chartModel->getActiveAccounts();
            include view_path('accounting/select_account');
            return;
        }

        $journalEntryModel = new JournalEntry();
        $ledger = $journalEntryModel->getGeneralLedger($accountCode, $startDate, $endDate);

        $chartModel = new ChartOfAccounts();
        $account = $chartModel->findByCode($accountCode);

        include view_path('accounting/general_ledger');
    }

    public function trialBalance(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'view');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $journalEntryModel = new JournalEntry();
        $trialBalance = $journalEntryModel->getTrialBalance($startDate, $endDate);

        include view_path('accounting/trial_balance');
    }

    public function balanceSheet(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'view');

        $date = $_GET['date'] ?? date('Y-m-d');

        $chartModel = new ChartOfAccounts();
        $balanceSheet = $chartModel->getBalanceSheet($date);

        include view_path('accounting/balance_sheet');
    }

    public function incomeStatement(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'view');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $chartModel = new ChartOfAccounts();
        $incomeStatement = $chartModel->getIncomeStatement($startDate, $endDate);

        include view_path('accounting/income_statement');
    }

    public function chartOfAccounts(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'view');

        $chartModel = new ChartOfAccounts();
        $hierarchy = $chartModel->getHierarchy();

        include view_path('accounting/chart_of_accounts');
    }

    // ===== API ENDPOINTS =====
    public function getAccountsApi(): void
    {
        require_login();

        $chartModel = new ChartOfAccounts();
        $accounts = $chartModel->getActiveAccounts();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'accounts' => $accounts]);
    }

    public function searchAccountsApi(): void
    {
        require_login();

        $query = $_GET['q'] ?? '';

        if (empty($query)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'accounts' => []]);
            return;
        }

        $chartModel = new ChartOfAccounts();
        $accounts = $chartModel->search($query);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'accounts' => $accounts]);
    }

    public function autoCreateJournalApi(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'create');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['type'], $input['reference_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Type and reference ID are required']);
            return;
        }

        $journalModel = new AccountingJournal();
        $journalId = null;

        try {
            switch ($input['type']) {
                case 'loan_disbursement':
                    $journalId = $journalModel->createLoanDisbursementJournal($input['reference_id']);
                    break;
                case 'repayment':
                    $journalId = $journalModel->createRepaymentJournal($input['reference_id']);
                    break;
                case 'savings_deposit':
                    $journalId = $journalModel->createSavingsDepositJournal($input['reference_id']);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Unknown journal type']);
                    return;
            }

            if ($journalId) {
                echo json_encode([
                    'success' => true,
                    'journal_id' => $journalId,
                    'message' => 'Journal created successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create journal']);
            }

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create journal: ' . $e->getMessage()]);
        }
    }

    // ===== REPORTING =====
    public function exportTrialBalance(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'export');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $journalEntryModel = new JournalEntry();
        $trialBalance = $journalEntryModel->getTrialBalance($startDate, $endDate);

        // CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="trial_balance_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Account Code', 'Account Name', 'Debit', 'Credit', 'Balance'
        ]);

        foreach ($trialBalance as $account) {
            fputcsv($output, [
                $account['account_code'],
                $account['account_name'],
                $account['total_debit'],
                $account['total_credit'],
                $account['balance']
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportGeneralLedger(): void
    {
        require_login();
        AuthHelper::requirePermission('accounting', 'export');

        $accountCode = $_GET['account'] ?? '';
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        if (empty($accountCode)) {
            http_response_code(400);
            echo 'Account code required';
            return;
        }

        $journalEntryModel = new JournalEntry();
        $ledger = $journalEntryModel->getGeneralLedger($accountCode, $startDate, $endDate);

        // CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="general_ledger_' . $accountCode . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Date', 'Reference', 'Description', 'Debit', 'Credit', 'Balance'
        ]);

        foreach ($ledger as $entry) {
            fputcsv($output, [
                $entry['transaction_date'],
                $entry['reference_number'],
                $entry['entry_description'] ?: $entry['journal_description'],
                $entry['debit'],
                $entry['credit'],
                $entry['balance']
            ]);
        }

        fclose($output);
        exit;
    }
}
