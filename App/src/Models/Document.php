<?php
namespace App\Models;

class Document extends Model
{
    protected string $table = 'generated_documents';

    /**
     * Generate loan agreement document
     */
    public function generateLoanAgreement(int $loanId): array
    {
        $loanModel = new Loan();
        $loan = $loanModel->findWithDetails($loanId);

        if (!$loan) {
            return ['error' => 'Loan not found'];
        }

        $memberModel = new Member();
        $member = $memberModel->find($loan['member_id']);

        if (!$member) {
            return ['error' => 'Member not found'];
        }

        // Get cooperative info
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($_SESSION['tenant']['id'] ?? null);

        // Generate document content
        $variables = [
            'document_number' => 'AGR/' . date('Y') . '/' . str_pad($loanId, 6, '0', STR_PAD_LEFT),
            'member_name' => $member['name'],
            'member_address' => $member['address'],
            'member_nik' => $member['nik'],
            'cooperative_name' => $tenant['name'] ?? 'KOPERASI APP',
            'loan_amount' => number_format($loan['principal_amount'], 0, ',', '.'),
            'loan_amount_text' => $this->numberToWords($loan['principal_amount']),
            'interest_rate' => $loan['interest_rate'],
            'tenor' => $loan['tenor_months'],
            'monthly_installment' => number_format($loan['monthly_installment'], 0, ',', '.'),
            'purpose' => $loan['purpose'] ?? 'Modal Usaha',
            'agreement_date' => date('d F Y'),
            'start_date' => date('d F Y'),
            'end_date' => date('d F Y', strtotime("+{$loan['tenor_months']} months"))
        ];

        $template = $this->getTemplateByType('loan_agreement');
        $content = $this->replaceVariables($template['template_content'], $variables);

        // Save generated document
        $documentId = $this->create([
            'template_id' => $template['id'],
            'reference_type' => 'loan',
            'reference_id' => $loanId,
            'document_number' => $variables['document_number'],
            'title' => 'Surat Perjanjian Pinjaman - ' . $member['name'],
            'content' => $content,
            'format' => 'html',
            'generated_by' => $_SESSION['user']['id'] ?? 1
        ]);

        return [
            'document_id' => $documentId,
            'document_number' => $variables['document_number'],
            'content' => $content,
            'title' => 'Surat Perjanjian Pinjaman - ' . $member['name'],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate SKB (Surat Kesepakatan Bersama)
     */
    public function generateSKB(int $loanId): array
    {
        $loanModel = new Loan();
        $loan = $loanModel->findWithDetails($loanId);

        if (!$loan) {
            return ['error' => 'Loan not found'];
        }

        $memberModel = new Member();
        $member = $memberModel->find($loan['member_id']);

        $variables = [
            'document_number' => 'SKB/' . date('Y') . '/' . str_pad($loanId, 6, '0', STR_PAD_LEFT),
            'member_name' => $member['name'],
            'member_address' => $member['address'],
            'loan_amount' => number_format($loan['principal_amount'], 0, ',', '.'),
            'loan_amount_text' => $this->numberToWords($loan['principal_amount']),
            'agreement_date' => date('d F Y'),
            'cooperative_name' => $_SESSION['tenant']['name'] ?? 'KOPERASI APP'
        ];

        $template = $this->getTemplateByType('skb');
        $content = $this->replaceVariables($template['template_content'], $variables);

        $documentId = $this->create([
            'template_id' => $template['id'],
            'reference_type' => 'loan',
            'reference_id' => $loanId,
            'document_number' => $variables['document_number'],
            'title' => 'Surat Kesepakatan Bersama - ' . $member['name'],
            'content' => $content,
            'format' => 'html',
            'generated_by' => $_SESSION['user']['id'] ?? 1
        ]);

        return [
            'document_id' => $documentId,
            'document_number' => $variables['document_number'],
            'content' => $content,
            'title' => 'Surat Kesepakatan Bersama - ' . $member['name'],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate somasi (warning letter)
     */
    public function generateSomasi(int $loanId): array
    {
        $loanModel = new Loan();
        $loan = $loanModel->findWithDetails($loanId);

        if (!$loan) {
            return ['error' => 'Loan not found'];
        }

        $memberModel = new Member();
        $member = $memberModel->find($loan['member_id']);

        // Calculate overdue amount and days
        $stmt = $this->db->prepare("
            SELECT lr.amount_due, lr.due_date,
                   DATEDIFF(CURDATE(), lr.due_date) as days_overdue
            FROM loan_repayments lr
            WHERE lr.loan_id = ? AND lr.status = 'pending'
            ORDER BY lr.due_date ASC
            LIMIT 1
        ");
        $stmt->execute([$loanId]);
        $overdue = $stmt->fetch();

        $variables = [
            'document_number' => 'SM/' . date('Y') . '/' . str_pad($loanId, 6, '0', STR_PAD_LEFT),
            'member_name' => $member['name'],
            'member_address' => $member['address'],
            'loan_number' => $loan['loan_number'],
            'overdue_amount' => number_format($overdue['amount_due'] ?? 0, 0, ',', '.'),
            'due_date' => date('d F Y', strtotime($overdue['due_date'] ?? 'now')),
            'days_overdue' => $overdue['days_overdue'] ?? 0,
            'warning_date' => date('d F Y'),
            'cooperative_name' => $_SESSION['tenant']['name'] ?? 'KOPERASI APP'
        ];

        $template = $this->getTemplateByType('somasi');
        $content = $this->replaceVariables($template['template_content'], $variables);

        $documentId = $this->create([
            'template_id' => $template['id'],
            'reference_type' => 'loan',
            'reference_id' => $loanId,
            'document_number' => $variables['document_number'],
            'title' => 'Surat Somasi - ' . $member['name'],
            'content' => $content,
            'format' => 'html',
            'generated_by' => $_SESSION['user']['id'] ?? 1
        ]);

        return [
            'document_id' => $documentId,
            'document_number' => $variables['document_number'],
            'content' => $content,
            'title' => 'Surat Somasi - ' . $member['name'],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate payment receipt
     */
    public function generateReceipt(int $repaymentId): array
    {
        $stmt = $this->db->prepare("
            SELECT lr.*, l.loan_number, m.name as member_name, m.phone
            FROM loan_repayments lr
            JOIN loans l ON lr.loan_id = l.id
            JOIN members m ON lr.member_id = m.id
            WHERE lr.id = ?
        ");
        $stmt->execute([$repaymentId]);
        $repayment = $stmt->fetch();

        if (!$repayment) {
            return ['error' => 'Repayment not found'];
        }

        $variables = [
            'receipt_number' => 'RCT/' . date('Y') . '/' . str_pad($repaymentId, 6, '0', STR_PAD_LEFT),
            'member_name' => $repayment['member_name'],
            'loan_number' => $repayment['loan_number'],
            'payment_date' => date('d F Y', strtotime($repayment['paid_date'])),
            'amount_paid' => number_format($repayment['amount_paid'], 0, ',', '.'),
            'installment_number' => $repayment['installment_number'],
            'cooperative_name' => $_SESSION['tenant']['name'] ?? 'KOPERASI APP',
            'received_by' => $_SESSION['user']['name'] ?? 'Admin'
        ];

        $template = $this->getTemplateByType('receipt');
        $content = $this->replaceVariables($template['template_content'], $variables);

        $documentId = $this->create([
            'template_id' => $template['id'],
            'reference_type' => 'repayment',
            'reference_id' => $repaymentId,
            'document_number' => $variables['receipt_number'],
            'title' => 'Bukti Pembayaran - ' . $repayment['member_name'],
            'content' => $content,
            'format' => 'html',
            'generated_by' => $_SESSION['user']['id'] ?? 1
        ]);

        return [
            'document_id' => $documentId,
            'receipt_number' => $variables['receipt_number'],
            'content' => $content,
            'title' => 'Bukti Pembayaran - ' . $repayment['member_name'],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate financial report
     */
    public function generateFinancialReport(string $reportType, string $startDate, string $endDate): array
    {
        $accountingModel = new Accounting();

        switch ($reportType) {
            case 'income_statement':
                $reportData = $accountingModel->generateIncomeStatement($startDate, $endDate);
                $title = 'Laporan Laba Rugi';
                break;
            case 'balance_sheet':
                $reportData = $accountingModel->generateBalanceSheet($endDate);
                $title = 'Neraca';
                break;
            case 'cash_flow':
                $reportData = $accountingModel->generateCashFlowStatement($startDate, $endDate);
                $title = 'Laporan Arus Kas';
                break;
            default:
                return ['error' => 'Invalid report type'];
        }

        $content = $this->formatReportContent($reportType, $reportData);

        $documentId = $this->create([
            'template_id' => null, // No template for reports
            'reference_type' => 'report',
            'reference_id' => null,
            'document_number' => strtoupper($reportType) . '/' . date('Y') . '/' . date('m'),
            'title' => $title . ' - ' . date('M Y', strtotime($startDate)) . ' s/d ' . date('M Y', strtotime($endDate)),
            'content' => $content,
            'format' => 'html',
            'generated_by' => $_SESSION['user']['id'] ?? 1
        ]);

        return [
            'document_id' => $documentId,
            'report_type' => $reportType,
            'title' => $title,
            'content' => $content,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get template by type
     */
    private function getTemplateByType(string $type): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM document_templates
            WHERE type = ? AND is_active = true
            LIMIT 1
        ");
        $stmt->execute([$type]);
        return $stmt->fetch();
    }

    /**
     * Replace variables in template
     */
    private function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    /**
     * Convert number to words (Indonesian)
     */
    private function numberToWords(int $number): string
    {
        // Simple implementation - in production, use a proper number-to-words library
        $units = ['', 'ribu', 'juta', 'milyar'];
        $numbers = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];

        if ($number < 1000) {
            return $numbers[$number] ?? $number;
        }

        $result = '';
        $unitIndex = 0;

        while ($number > 0) {
            $chunk = $number % 1000;
            if ($chunk > 0) {
                $chunkText = $numbers[$chunk] ?? $chunk;
                $result = $chunkText . ' ' . $units[$unitIndex] . ' ' . $result;
            }
            $number = (int)($number / 1000);
            $unitIndex++;
        }

        return trim($result) . ' rupiah';
    }

    /**
     * Format report content
     */
    private function formatReportContent(string $reportType, array $data): string
    {
        $html = '<div class="report-container">';
        $html .= '<h2 class="report-title">' . ucwords(str_replace('_', ' ', $reportType)) . '</h2>';
        $html .= '<div class="report-period">Periode: ' . ($data['period']['start'] ?? '') . ' - ' . ($data['period']['end'] ?? '') . '</div>';

        switch ($reportType) {
            case 'income_statement':
                $html .= $this->formatIncomeStatement($data);
                break;
            case 'balance_sheet':
                $html .= $this->formatBalanceSheet($data);
                break;
            case 'cash_flow':
                $html .= $this->formatCashFlow($data);
                break;
        }

        $html .= '<div class="report-footer">';
        $html .= '<p>Dicetak pada: ' . date('d F Y H:i:s') . '</p>';
        $html .= '<p>Dibuat oleh: ' . ($_SESSION['user']['name'] ?? 'System') . '</p>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Format income statement HTML
     */
    private function formatIncomeStatement(array $data): string
    {
        $html = '<table class="report-table">';
        $html .= '<thead><tr><th>Keterangan</th><th>Jumlah</th></tr></thead>';
        $html .= '<tbody>';

        $html .= '<tr class="section-header"><td colspan="2"><strong>PENDAPATAN</strong></td></tr>';
        foreach ($data['revenues'] as $revenue) {
            $html .= "<tr><td>{$revenue['name']}</td><td>Rp " . number_format($revenue['amount'], 0, ',', '.') . "</td></tr>";
        }
        $html .= "<tr class=\"total-row\"><td><strong>Total Pendapatan</strong></td><td><strong>Rp " . number_format($data['total_revenue'], 0, ',', '.') . "</strong></td></tr>";

        $html .= '<tr class="section-header"><td colspan="2"><strong>BEBAN</strong></td></tr>';
        foreach ($data['expenses'] as $expense) {
            $html .= "<tr><td>{$expense['name']}</td><td>Rp " . number_format($expense['amount'], 0, ',', '.') . "</td></tr>";
        }
        $html .= "<tr class=\"total-row\"><td><strong>Total Beban</strong></td><td><strong>Rp " . number_format($data['total_expenses'], 0, ',', '.') . "</strong></td></tr>";

        $html .= "<tr class=\"grand-total\"><td><strong>LABA BERSIH</strong></td><td><strong>Rp " . number_format($data['net_income'], 0, ',', '.') . "</strong></td></tr>";

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Format balance sheet HTML
     */
    private function formatBalanceSheet(array $data): string
    {
        $html = '<div class="balance-sheet">';
        $html .= '<div class="balance-section">';
        $html .= '<h3>AKTIVA</h3>';
        $html .= '<table class="report-table">';

        foreach ($data['accounts']['asset'] ?? [] as $account) {
            $html .= "<tr><td>{$account['name']}</td><td>Rp " . number_format($account['balance'], 0, ',', '.') . "</td></tr>";
        }

        $html .= '</table></div>';

        $html .= '<div class="balance-section">';
        $html .= '<h3>PASIVA & EKUITAS</h3>';
        $html .= '<table class="report-table">';

        foreach ($data['accounts']['liability'] ?? [] as $account) {
            $html .= "<tr><td>{$account['name']}</td><td>Rp " . number_format($account['balance'], 0, ',', '.') . "</td></tr>";
        }

        foreach ($data['accounts']['equity'] ?? [] as $account) {
            $html .= "<tr><td>{$account['name']}</td><td>Rp " . number_format($account['balance'], 0, ',', '.') . "</td></tr>";
        }

        $html .= "<tr class=\"total-row\"><td><strong>Total Pasiva & Ekuitas</strong></td><td><strong>Rp " . number_format($data['totals']['liabilities_and_equity'], 0, ',', '.') . "</strong></td></tr>";

        $html .= '</table></div></div>';
        return $html;
    }

    /**
     * Format cash flow HTML
     */
    private function formatCashFlow(array $data): string
    {
        $html = '<table class="report-table">';
        $html .= '<tr><td>Aktivitas Operasi</td><td>Rp ' . number_format($data['operating_activities'], 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td>Aktivitas Investasi</td><td>Rp ' . number_format($data['investing_activities'], 0, ',', '.') . '</td></tr>';
        $html .= '<tr><td>Aktivitas Pendanaan</td><td>Rp ' . number_format($data['financing_activities'], 0, ',', '.') . '</td></tr>';
        $html .= '<tr class="total-row"><td><strong>Arus Kas Bersih</strong></td><td><strong>Rp ' . number_format($data['net_cash_flow'], 0, ',', '.') . '</strong></td></tr>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Export document to PDF
     */
    public function exportToPDF(int $documentId): string
    {
        $document = $this->find($documentId);
        if (!$document) {
            throw new \Exception('Document not found');
        }

        // In a real implementation, you would use a PDF library like TCPDF or DomPDF
        // For now, we'll just return a placeholder
        $pdfPath = 'documents/pdf/' . $document['document_number'] . '.pdf';

        // Update document with PDF path
        $this->update($documentId, ['file_path' => $pdfPath]);

        return $pdfPath;
    }

    /**
     * Export report to Excel
     */
    public function exportToExcel(string $reportType, array $data): string
    {
        // In a real implementation, you would use a library like PhpSpreadsheet
        // For now, we'll return a CSV format as placeholder
        $excelContent = "Report Type: {$reportType}\n";
        $excelContent .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

        if (isset($data['revenues'])) {
            $excelContent .= "Revenues:\n";
            foreach ($data['revenues'] as $revenue) {
                $excelContent .= "{$revenue['name']},{$revenue['amount']}\n";
            }
        }

        $filename = 'reports/excel/' . $reportType . '_' . date('Y-m-d') . '.csv';
        file_put_contents($filename, $excelContent);

        return $filename;
    }

    /**
     * Archive document
     */
    public function archiveDocument(int $documentId): bool
    {
        return $this->update($documentId, [
            'status' => 'archived',
            'archived_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get documents by type
     */
    public function getDocumentsByType(string $referenceType, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE reference_type = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$referenceType, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Search documents
     */
    public function searchDocuments(string $query, string $referenceType = null): array
    {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE (title LIKE ? OR document_number LIKE ? OR content LIKE ?)
        ";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];

        if ($referenceType) {
            $sql .= " AND reference_type = ?";
            $params[] = $referenceType;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
