<?php
/**
 * KSP Components - Fixed Version
 * Koperasi Simpan Pinjam Components Library
 */

class KSP_Components
{
    /**
     * Generate stats cards row
     */
    public static function statsCards($stats) {
        $html = '<div class="row g-4 mb-4">';

        foreach ($stats as $stat) {
            $color = $stat['color'] ?? 'primary';
            $icon = $stat['icon'] ?? '<i class="bi bi-info-circle"></i>';
            $value = $stat['value'];
            $label = $stat['label'];
            
            $html .= "
                <div class='col-xl-3 col-lg-6 col-md-6 col-sm-12'>
                    <div class='card border-0 shadow-sm h-100'>
                        <div class='card-body text-center'>
                            <div class='display-4 text-{$color} mb-2'>
                                {$icon}
                            </div>
                            <h3 class='fw-bold'>{$value}</h3>
                            <p class='text-muted mb-0'>{$label}</p>
                        </div>
                    </div>
                </div>
            ";
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Generate member card
     */
    public static function memberCard($member) {
        $status = $member['status'] ?? 'pending';
        $statusColor = $status === 'active' ? 'success' : 'warning';
        
        return "
            <div class='card mb-3'>
                <div class='card-body'>
                    <div class='d-flex align-items-center'>
                        <div class='flex-shrink-0'>
                            <img src='" . asset_url('images/default-avatar.png') . "' alt='Avatar' class='rounded-circle' width='50' height='50'>
                        </div>
                        <div class='flex-grow-1 ms-3'>
                            <h5 class='mb-1'>{$member['name']}</h5>
                            <p class='mb-0 text-muted'>{$member['nik']}</p>
                        </div>
                        <div class='flex-shrink-0'>
                            <span class='badge bg-{$statusColor}'>{$status}</span>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }

    /**
     * Generate loan status badge
     */
    public static function loanStatusBadge($status) {
        $colors = [
            'pending' => 'warning',
            'approved' => 'info',
            'disbursed' => 'primary',
            'completed' => 'success',
            'rejected' => 'danger'
        ];
        
        $color = $colors[$status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($status) . "</span>";
    }

    /**
     * Generate savings summary
     */
    public static function savingsSummary($savings) {
        $total = 0;
        foreach ($savings as $saving) {
            $total += $saving['amount'];
        }
        
        return "
            <div class='card'>
                <div class='card-header'>
                    <h5 class='card-title mb-0'>Savings Summary</h5>
                </div>
                <div class='card-body'>
                    <div class='row'>
                        <div class='col-md-6'>
                            <h6>Total Savings</h6>
                            <h3 class='text-primary'>" . number_format($total, 2, ',', '.') . "</h3>
                        </div>
                        <div class='col-md-6'>
                            <h6>Active Accounts</h6>
                            <h3 class='text-info'>" . count($savings) . "</h3>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }

    /**
     * Generate SHU distribution chart
     */
    public static function shuDistribution($distribution) {
        $html = '<div class="card">';
        $html .= '<div class="card-header"><h5 class="card-title mb-0">SHU Distribution</h5></div>';
        $html .= '<div class="card-body">';
        
        foreach ($distribution as $category => $amount) {
            $percentage = ($amount / array_sum($distribution)) * 100;
            $html .= "
                <div class='mb-3'>
                    <div class='d-flex justify-content-between align-items-center'>
                        <span>" . ucfirst($category) . "</span>
                        <span>" . number_format($amount, 2, ',', '.') . " (" . number_format($percentage, 1) . "%)</span>
                    </div>
                    <div class='progress'>
                        <div class='progress-bar' style='width: {$percentage}%'></div>
                    </div>
                </div>
            ";
        }
        
        $html .= '</div></div>';
        return $html;
    }
}
?>
