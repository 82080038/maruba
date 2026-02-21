<?php
// KSP Reusable UI Components Library
// Pre-built components for consistent UI across the application

class KSP_Components {

    // =========================================
    // DASHBOARD COMPONENTS
    // =========================================

    /**
     * Generate dashboard metrics cards
     */
    public static function dashboardMetrics($metrics) {
        $html = '<div class="row g-4">';

        $cards = [
            [
                'title' => 'Total Anggota',
                'value' => $metrics['total_members'] ?? 0,
                'icon' => 'bi-people-fill',
                'color' => 'primary',
                'change' => $metrics['members_change'] ?? null
            ],
            [
                'title' => 'Pinjaman Aktif',
                'value' => $metrics['active_loans'] ?? 0,
                'icon' => 'bi-cash-stack',
                'color' => 'success',
                'change' => $metrics['loans_change'] ?? null
            ],
            [
                'title' => 'Outstanding',
                'value' => 'Rp ' . number_format($metrics['total_outstanding'] ?? 0, 0, ',', '.'),
                'icon' => 'bi-currency-dollar',
                'color' => 'warning',
                'change' => $metrics['outstanding_change'] ?? null
            ],
            [
                'title' => 'NPL Ratio',
                'value' => number_format($metrics['npl_ratio'] ?? 0, 1) . '%',
                'icon' => 'bi-exclamation-triangle-fill',
                'color' => ($metrics['npl_ratio'] ?? 0) > 5 ? 'danger' : 'info',
                'change' => $metrics['npl_change'] ?? null
            ]
        ];

        foreach ($cards as $card) {
            $html .= self::metricCard($card);
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Generate single metric card
     */
    private static function metricCard($data) {
        $changeHtml = '';
        if (isset($data['change'])) {
            $changeClass = $data['change'] >= 0 ? 'text-success' : 'text-danger';
            $changeIcon = $data['change'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down';
            $changeHtml = "<small class='{$changeClass}'><i class='bi {$changeIcon} me-1'></i>" . abs($data['change']) . "%</small>";
        }

        return "
            <div class='col-xl-3 col-lg-6 col-md-6 col-sm-12'>
                <div class='card border-0 shadow-sm h-100'>
                    <div class='card-body'>
                        <div class='d-flex align-items-center justify-content-between'>
                            <div>
                                <h6 class='card-title text-muted mb-1'>{$data['title']}</h6>
                                <h4 class='card-text mb-0 fw-bold'>{$data['value']}</h4>
                                {$changeHtml}
                            </div>
                            <div class='bg-{$data['color']} bg-opacity-10 rounded-circle p-3'>
                                <i class='bi {$data['icon']} fs-2 text-{$data['color']}'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }

    /**
     * Generate recent activities list
     */
    public static function recentActivities($activities, $limit = 10) {
        $html = '
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi bi-activity me-2 text-primary"></i>Aktivitas Terkini
                    </h6>
                </div>
                <div class="card-body">
                    <div class="activity-list">
        ';

        if (empty($activities)) {
            $html .= '<p class="text-muted mb-0">Tidak ada aktivitas terkini.</p>';
        } else {
            foreach (array_slice($activities, 0, $limit) as $activity) {
                $icon = self::getActivityIcon($activity['action']);
                $time = self::timeAgo($activity['created_at']);

                $html .= "
                    <div class='activity-item d-flex align-items-start mb-3'>
                        <div class='activity-icon bg-primary bg-opacity-10 rounded-circle p-2 me-3'>
                            <i class='bi {$icon} text-primary'></i>
                        </div>
                        <div class='flex-grow-1'>
                            <div class='d-flex justify-content-between align-items-start'>
                                <div>
                                    <p class='mb-1 fw-medium'>
                                        <span class='text-primary'>{$activity['user_name']}</span>
                                        {$activity['action']} {$activity['entity']}
                                        " . (!empty($activity['entity_id']) ? "<strong>#{$activity['entity_id']}</strong>" : "") . "
                                    </p>
                                </div>
                                <small class='text-muted'>{$time}</small>
                            </div>
                        </div>
                    </div>
                ";
            }
        }

        $html .= '
                    </div>
                </div>
            </div>
        ';

        return $html;
    }

    /**
     * Get activity icon based on action
     */
    private static function getActivityIcon($action) {
        $icons = [
            'create' => 'bi-plus-circle',
            'update' => 'bi-pencil',
            'delete' => 'bi-trash',
            'approve' => 'bi-check-circle',
            'reject' => 'bi-x-circle',
            'login' => 'bi-box-arrow-in-right',
            'logout' => 'bi-box-arrow-right'
        ];

        foreach ($icons as $key => $icon) {
            if (strpos(strtolower($action), $key) !== false) {
                return $icon;
            }
        }

        return 'bi-circle';
    }

    /**
     * Convert timestamp to time ago format
     */
    private static function timeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return $diff . ' detik lalu';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' menit lalu';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' jam lalu';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . ' hari lalu';
        } else {
            return date('d M Y', $time);
        }
    }

    // =========================================
    // DATA TABLES
    // =========================================

    /**
     * Generate members data table
     */
    public static function membersTable($members = [], $actions = true) {
        $html = '
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi bi-people me-2 text-primary"></i>Daftar Anggota
                    </h6>
                    <button class="btn btn-primary btn-sm" onclick="KSP.Components.createMemberModal()">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Anggota
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="membersTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">Nama</th>
                                    <th width="15%">NIK</th>
                                    <th width="12%">Telepon</th>
                                    <th width="10%">Status</th>
                                    <th width="15%">Bergabung</th>
                                    ' . ($actions ? '<th width="15%">Aksi</th>' : '') . '
                                </tr>
                            </thead>
                            <tbody>
        ';

        if (empty($members)) {
            $colspan = $actions ? 7 : 6;
            $html .= "<tr><td colspan='{$colspan}' class='text-center text-muted py-4'>Belum ada data anggota</td></tr>";
        } else {
            foreach ($members as $index => $member) {
                $statusBadge = self::statusBadge($member['status']);
                $joinDate = date('d M Y', strtotime($member['created_at']));

                $html .= "
                    <tr>
                        <td>" . ($index + 1) . "</td>
                        <td>
                            <div class='d-flex align-items-center'>
                                <div class='bg-primary bg-opacity-10 rounded-circle p-2 me-3'>
                                    <i class='bi bi-person text-primary'></i>
                                </div>
                                <div>
                                    <div class='fw-medium'>{$member['name']}</div>
                                    <small class='text-muted'>{$member['email']}</small>
                                </div>
                            </div>
                        </td>
                        <td>{$member['nik']}</td>
                        <td>{$member['phone']}</td>
                        <td>{$statusBadge}</td>
                        <td>{$joinDate}</td>
                        " . ($actions ? "
                        <td>
                            <div class='btn-group btn-group-sm'>
                                <button class='btn btn-outline-primary' onclick='KSP.Components.viewMember({$member['id']})' title='Lihat Detail'>
                                    <i class='bi bi-eye'></i>
                                </button>
                                <button class='btn btn-outline-warning' onclick='KSP.Components.editMember({$member['id']})' title='Edit'>
                                    <i class='bi bi-pencil'></i>
                                </button>
                                <button class='btn btn-outline-danger' onclick='KSP.Components.deleteMember({$member['id']}, \"{$member['name']}\")' title='Hapus'>
                                    <i class='bi bi-trash'></i>
                                </button>
                            </div>
                        </td>" : '') . "
                    </tr>
                ";
            }
        }

        $html .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        ';

        return $html;
    }

    /**
     * Generate loans data table
     */
    public static function loansTable($loans = [], $actions = true) {
        $html = '
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi bi-cash-stack me-2 text-primary"></i>Daftar Pinjaman
                    </h6>
                    <button class="btn btn-primary btn-sm" onclick="KSP.Components.createLoanModal()">
                        <i class="bi bi-plus-circle me-1"></i>Ajukan Pinjaman
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="loansTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="12%">No. Pinjaman</th>
                                    <th width="18%">Anggota</th>
                                    <th width="15%">Jumlah</th>
                                    <th width="8%">Tenor</th>
                                    <th width="12%">Status</th>
                                    <th width="15%">Tanggal</th>
                                    ' . ($actions ? '<th width="15%">Aksi</th>' : '') . '
                                </tr>
                            </thead>
                            <tbody>
        ';

        if (empty($loans)) {
            $colspan = $actions ? 8 : 7;
            $html .= "<tr><td colspan='{$colspan}' class='text-center text-muted py-4'>Belum ada data pinjaman</td></tr>";
        } else {
            foreach ($loans as $index => $loan) {
                $statusBadge = self::loanStatusBadge($loan['status']);
                $amount = 'Rp ' . number_format($loan['principal_amount'], 0, ',', '.');
                $applicationDate = date('d M Y', strtotime($loan['application_date']));

                $html .= "
                    <tr>
                        <td>" . ($index + 1) . "</td>
                        <td>
                            <span class='badge bg-light text-dark fw-medium'>{$loan['loan_number']}</span>
                        </td>
                        <td>
                            <div>
                                <div class='fw-medium'>{$loan['member_name']}</div>
                                <small class='text-muted'>{$loan['product_name']}</small>
                            </div>
                        </td>
                        <td class='fw-medium'>{$amount}</td>
                        <td>{$loan['tenor_months']} bulan</td>
                        <td>{$statusBadge}</td>
                        <td>{$applicationDate}</td>
                        " . ($actions ? "
                        <td>
                            <div class='btn-group btn-group-sm'>
                                <button class='btn btn-outline-primary' onclick='KSP.Components.viewLoan({$loan['id']})' title='Lihat Detail'>
                                    <i class='bi bi-eye'></i>
                                </button>
                                <button class='btn btn-outline-info' onclick='KSP.Components.viewLoanSchedule({$loan['id']})' title='Jadwal Angsuran'>
                                    <i class='bi bi-calendar-event'></i>
                                </button>
                            </div>
                        </td>" : '') . "
                    </tr>
                ";
            }
        }

        $html .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        ';

        return $html;
    }

    // =========================================
    // FORM COMPONENTS
    // =========================================

    /**
     * Generate status badge
     */
    public static function statusBadge($status) {
        $badges = [
            'active' => '<span class="badge badge-status-active">Aktif</span>',
            'inactive' => '<span class="badge badge-status-inactive">Tidak Aktif</span>',
            'pending' => '<span class="badge badge-status-pending">Menunggu</span>',
            'rejected' => '<span class="badge badge-status-rejected">Ditolak</span>',
            'suspended' => '<span class="badge bg-secondary">Ditangguhkan</span>',
            'blacklisted' => '<span class="badge bg-dark">Diblacklist</span>'
        ];

        return $badges[$status] ?? "<span class='badge bg-secondary'>{$status}</span>";
    }

    /**
     * Generate loan status badge
     */
    public static function loanStatusBadge($status) {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'submitted' => '<span class="badge bg-info">Diajukan</span>',
            'survey_pending' => '<span class="badge bg-warning">Survey</span>',
            'survey_completed' => '<span class="badge bg-primary">Survey Selesai</span>',
            'approved' => '<span class="badge bg-success">Disetujui</span>',
            'disbursed' => '<span class="badge bg-success">Dicairkan</span>',
            'active' => '<span class="badge bg-success">Aktif</span>',
            'completed' => '<span class="badge bg-secondary">Lunas</span>',
            'rejected' => '<span class="badge bg-danger">Ditolak</span>',
            'defaulted' => '<span class="badge bg-danger">Macet</span>'
        ];

        return $badges[$status] ?? "<span class='badge bg-secondary'>{$status}</span>";
    }

    /**
     * Generate savings status badge
     */
    public static function savingsStatusBadge($status) {
        $badges = [
            'active' => '<span class="badge bg-success">Aktif</span>',
            'inactive' => '<span class="badge bg-secondary">Tidak Aktif</span>',
            'frozen' => '<span class="badge bg-warning">Dibekukan</span>',
            'closed' => '<span class="badge bg-dark">Ditutup</span>'
        ];

        return $badges[$status] ?? "<span class='badge bg-secondary'>{$status}</span>";
    }

    // =========================================
    // CHART COMPONENTS
    // =========================================

    /**
     * Generate simple chart container
     */
    public static function chartContainer($title, $chartId, $height = '300px') {
        return "
            <div class='card border-0 shadow-sm'>
                <div class='card-header bg-white border-0'>
                    <h6 class='card-title mb-0 fw-bold'>
                        <i class='bi bi-graph-up me-2 text-primary'></i>{$title}
                    </h6>
                </div>
                <div class='card-body'>
                    <canvas id='{$chartId}' style='height: {$height};'></canvas>
                </div>
            </div>
        ";
    }

    // =========================================
    // LAYOUT COMPONENTS
    // =========================================

    /**
     * Generate page header
     */
    public static function pageHeader($title, $subtitle = '', $actions = []) {
        $actionsHtml = '';
        if (!empty($actions)) {
            $actionsHtml = '<div class="d-flex gap-2">';
            foreach ($actions as $action) {
                $class = $action['class'] ?? 'btn-primary';
                $icon = $action['icon'] ?? '';
                $text = $action['text'] ?? '';
                $onclick = $action['onclick'] ?? '';

                $actionsHtml .= "<button class='btn {$class} btn-sm' onclick='{$onclick}'>{$icon} {$text}</button>";
            }
            $actionsHtml .= '</div>';
        }

        return "
            <div class='row align-items-center mb-4'>
                <div class='col'>
                    <h4 class='mb-1 fw-bold'>{$title}</h4>
                    " . ($subtitle ? "<p class='text-muted mb-0'>{$subtitle}</p>" : '') . "
                </div>
                " . ($actionsHtml ? "<div class='col-auto'>{$actionsHtml}</div>" : '') . "
            </div>
        ";
    }

    /**
     * Generate stats cards row
     */
    public static function statsCards($stats) {
        $html = '<div class="row g-4 mb-4">';

        foreach ($stats as $stat) {
            $html .= "
                <div class='col-xl-3 col-lg-6 col-md-6 col-sm-12'>
                    <div class='card border-0 shadow-sm h-100'>
                        <div class='card-body text-center'>
                            <div class='display-4 text-{$stat['color'] ?? 'primary'} mb-2'>
                                {$stat['icon'] ?? '<i class="bi bi-info-circle"></i>'}
                            </div>
                            <h3 class='fw-bold'>{$stat['value']}</h3>
                            <p class='text-muted mb-0'>{$stat['label']}</p>
                        </div>
                    </div>
                </div>
            ";
        }

        $html .= '</div>';
        return $html;
    }

    // =========================================
    // MODAL COMPONENTS
    // =========================================

    /**
     * Generate confirmation modal
     */
    public static function confirmationModal($id, $title, $message, $confirmText = 'Ya, Lanjutkan', $cancelText = 'Batal') {
        return "
            <div class='modal fade' id='{$id}' tabindex='-1'>
                <div class='modal-dialog'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title'>{$title}</h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                        </div>
                        <div class='modal-body'>
                            <p class='mb-0'>{$message}</p>
                        </div>
                        <div class='modal-footer'>
                            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>{$cancelText}</button>
                            <button type='button' class='btn btn-primary' id='{$id}ConfirmBtn'>{$confirmText}</button>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }

    // =========================================
    // UTILITY METHODS
    // =========================================

    /**
     * Format currency
     */
    public static function formatCurrency($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Format date
     */
    public static function formatDate($date) {
        return date('d M Y', strtotime($date));
    }

    /**
     * Format date and time
     */
    public static function formatDateTime($datetime) {
        return date('d M Y H:i', strtotime($datetime));
    }

    /**
     * Generate random ID
     */
    public static function randomId($prefix = 'ksp') {
        return $prefix . '-' . uniqid() . '-' . rand(1000, 9999);
    }
}

// =========================================
// HELPER FUNCTIONS FOR TEMPLATES
// =========================================

function ksp_dashboard_metrics($metrics) {
    return KSP_Components::dashboardMetrics($metrics);
}

function ksp_recent_activities($activities, $limit = 10) {
    return KSP_Components::recentActivities($activities, $limit);
}

function ksp_members_table($members = [], $actions = true) {
    return KSP_Components::membersTable($members, $actions);
}

function ksp_loans_table($loans = [], $actions = true) {
    return KSP_Components::loansTable($loans, $actions);
}

function ksp_page_header($title, $subtitle = '', $actions = []) {
    return KSP_Components::pageHeader($title, $subtitle, $actions);
}

function ksp_stats_cards($stats) {
    return KSP_Components::statsCards($stats);
}

function ksp_status_badge($status) {
    return KSP_Components::statusBadge($status);
}

function ksp_loan_status_badge($status) {
    return KSP_Components::loanStatusBadge($status);
}

function ksp_chart_container($title, $chartId, $height = '300px') {
    return KSP_Components::chartContainer($title, $chartId, $height);
}

function ksp_confirmation_modal($id, $title, $message, $confirmText = 'Ya, Lanjutkan', $cancelText = 'Batal') {
    return KSP_Components::confirmationModal($id, $title, $message, $confirmText, $cancelText);
}

function ksp_format_currency($amount) {
    return KSP_Components::formatCurrency($amount);
}

function ksp_format_date($date) {
    return KSP_Components::formatDate($date);
}

function ksp_format_datetime($datetime) {
    return KSP_Components::formatDateTime($datetime);
}
?>
