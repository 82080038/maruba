<?php
ob_start();
?>
<?php
  $u = current_user();
  $displayName = $u['name'] ?? ($u['username'] ?? 'User');
  $role = user_role() ?: ($u['role'] ?? '-');

  // Get tenant info from middleware
  $tenantInfo = \App\Middleware\TenantMiddleware::getTenantInfo();
  $tenantName = $tenantInfo['name'] ?? 'Koperasi';

  // Sapaan waktu dalam bahasa Indonesia
  $h = (int) date('G'); // 0-23
  if ($h < 11) {
      $sapaan = 'Pagi';
  } elseif ($h < 15) {
      $sapaan = 'Siang';
  } elseif ($h < 18) {
      $sapaan = 'Sore';
  } else {
      $sapaan = 'Malam';
  }
?>

<div class="tenant-dashboard-container">
    <!-- Tenant Header -->
    <div class="tenant-header">
        <div>
            <h1 class="tenant-title"><?php echo htmlspecialchars($tenantName); ?> - Dashboard</h1>
            <p class="text-muted">Selamat <?php echo htmlspecialchars($sapaan); ?>, <?php echo htmlspecialchars($displayName); ?> (<?php echo htmlspecialchars($role); ?>)</p>
        </div>
        <div class="header-actions">
            <button id="refresh-tenant-dashboard" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                Refresh
            </button>
            <label class="form-check-label">
                <input type="checkbox" id="auto-refresh-toggle" class="form-check-input" checked>
                Auto Refresh (30s)
            </label>
        </div>
    </div>

    <!-- Tenant Metrics Grid -->
    <div class="metrics-grid" id="tenant-metrics-grid">
        <!-- Metrics will be loaded via JavaScript -->
        <div class="loading">
            <div class="spinner"></div>
            Memuat data dashboard...
        </div>
    </div>

    <!-- Tenant Alerts Section -->
    <div class="alerts-section" id="tenant-alerts-section">
        <!-- Alerts will be loaded via JavaScript -->
    </div>

    <!-- Tenant Charts Section -->
    <div class="charts-section">
        <div class="chart-card">
            <h3 class="chart-title">Status Pinjaman</h3>
            <div id="tenant-loan-status-chart">
                <div class="loading">
                    <div class="spinner"></div>
                    Memuat chart...
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3 class="chart-title">Trend Pembayaran</h3>
            <div id="tenant-payment-trend-chart">
                <div class="loading">
                    <div class="spinner"></div>
                    Memuat chart...
                </div>
            </div>
        </div>
    </div>

    <!-- Tenant Recent Activities Table -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Aksi</th>
                    <th>Entitas</th>
                    <th>Pengguna</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody id="tenant-activities-table">
                <!-- Activities will be loaded via JavaScript -->
                <tr>
                    <td colspan="4" class="text-center">
                        <div class="loading">
                            <div class="spinner"></div>
                            Memuat aktivitas...
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Tenant Quick Actions -->
    <div class="quick-actions">
        <h3>Aksi Cepat</h3>
        <div class="actions-grid">
            <a href="<?php echo route_url('members/create'); ?>" class="action-card">
                <i class="bi bi-person-plus"></i>
                <span>Tambah Anggota</span>
            </a>
            <a href="<?php echo route_url('loans/create'); ?>" class="action-card">
                <i class="bi bi-cash-stack"></i>
                <span>Ajukan Pinjaman</span>
            </a>
            <a href="<?php echo route_url('surveys'); ?>" class="action-card">
                <i class="bi bi-search"></i>
                <span>Survey Lapangan</span>
            </a>
            <a href="<?php echo route_url('repayments'); ?>" class="action-card">
                <i class="bi bi-receipt"></i>
                <span>Kelola Pembayaran</span>
            </a>
        </div>
    </div>
</div>

<script>
// Tenant-specific dashboard JavaScript
class TenantDashboardApp {
    constructor() {
        this.apiBase = '/api/tenant'; // Tenant API endpoint
        this.init();
    }

    init() {
        this.loadTenantDashboardData();
        this.setupEventListeners();
        this.startAutoRefresh();
    }

    async loadTenantDashboardData() {
        try {
            const response = await fetch(`${this.apiBase}/dashboard`);
            const data = await response.json();

            this.updateMetrics(data.metrics);
            this.updateAlerts(data.alerts);
            this.updateCharts(data);
            this.updateActivities(data.activities || []);
        } catch (error) {
            console.error('Error loading tenant dashboard data:', error);
            this.showError('Gagal memuat data dashboard');
        }
    }

    updateMetrics(metrics) {
        const metricsContainer = document.querySelector('.metrics-grid');
        if (!metricsContainer) return;

        metricsContainer.innerHTML = metrics.map(metric => `
            <div class="metric-card">
                <div class="metric-title">${metric.label}</div>
                <div class="metric-value">${this.formatValue(metric.value, metric.type)}</div>
            </div>
        `).join('');
    }

    updateAlerts(alerts) {
        const alertsContainer = document.querySelector('.alerts-section');
        if (!alertsContainer) return;

        const overdueCount = alerts.overdue ? alerts.overdue.length : 0;
        const dueThisWeekCount = alerts.due_week ? alerts.due_week.length : 0;

        alertsContainer.innerHTML = `
            <div class="alert-card ${overdueCount > 0 ? 'alert-danger' : 'alert-success'}">
                <div class="alert-title">Pembayaran Terlambat</div>
                <div class="alert-count">${overdueCount}</div>
            </div>
            <div class="alert-card alert-warning">
                <div class="alert-title">Jatuh Tempo Minggu Ini</div>
                <div class="alert-count">${dueThisWeekCount}</div>
            </div>
        `;
    }

    updateCharts(data) {
        // Simple chart implementation
        this.createSimpleChart('tenant-loan-status-chart', data.loanStatusData || []);
        this.createSimpleChart('tenant-payment-trend-chart', data.paymentTrendData || []);
    }

    updateActivities(activities) {
        const tbody = document.getElementById('tenant-activities-table');
        if (!tbody || !activities.length) return;

        tbody.innerHTML = activities.map(act => `
            <tr>
                <td>${act.action}</td>
                <td>${act.entity || '-'}</td>
                <td>${act.user_name || '-'}</td>
                <td>${new Date(act.created_at).toLocaleString('id-ID')}</td>
            </tr>
        `).join('');
    }

    createSimpleChart(containerId, data) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const maxValue = Math.max(...data.map(d => d.value || 0));
        container.innerHTML = `
            <div class="simple-chart">
                ${data.map(item => `
                    <div class="chart-bar">
                        <div class="bar-fill" style="height: ${(item.value / Math.max(maxValue, 1)) * 100}%"></div>
                        <div class="bar-label">${item.label}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    formatValue(value, type) {
        switch (type) {
            case 'currency':
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
            case 'percent':
                return value + '%';
            default:
                return new Intl.NumberFormat('id-ID').format(value);
        }
    }

    setupEventListeners() {
        const refreshBtn = document.getElementById('refresh-tenant-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadTenantDashboardData());
        }

        const autoRefreshToggle = document.getElementById('auto-refresh-toggle');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            });
        }
    }

    startAutoRefresh() {
        this.stopAutoRefresh();
        this.refreshInterval = setInterval(() => {
            this.loadTenantDashboardData();
        }, 30000);
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';
        errorDiv.textContent = message;

        const container = document.querySelector('.tenant-dashboard-container') || document.body;
        container.insertBefore(errorDiv, container.firstChild);

        setTimeout(() => errorDiv.remove(), 5000);
    }
}

// Initialize tenant dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.tenantDashboardApp = new TenantDashboardApp();
});
</script>

<style>
.quick-actions {
    margin-top: 2rem;
}

.quick-actions h3 {
    margin-bottom: 1rem;
    color: var(--gray-900);
    font-size: 1.25rem;
    font-weight: 600;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    text-decoration: none;
    color: var(--gray-700);
    transition: transform 0.2s, box-shadow 0.2s;
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    color: var(--primary-color);
}

.action-card i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.action-card span {
    font-weight: 500;
    text-align: center;
}
</style>

<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
?>
