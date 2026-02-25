// Dashboard JavaScript - Modern Frontend for KOPERASI APP
class DashboardApp {
    constructor() {
        this.apiBase = '/maruba/api';
        this.init();
    }

    init() {
        this.loadDashboardData();
        this.setupEventListeners();
        this.startAutoRefresh();
    }

    async loadDashboardData() {
        try {
            const response = await fetch(`${this.apiBase}/dashboard`);
            const data = await response.json();

            this.updateMetrics(data.metrics);
            this.updateAlerts(data.alerts);
            this.updateCharts(data);
        } catch (error) {
            console.error('Error loading dashboard data:', error);
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

        const overdueCount = alerts.overdue.length;
        const dueThisWeekCount = alerts.due_week.length;

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
        // Simple chart implementation - could be enhanced with Chart.js
        this.createSimpleChart('loan-status-chart', data.loanStatusData || []);
        this.createSimpleChart('payment-trend-chart', data.paymentTrendData || []);
    }

    createSimpleChart(containerId, data) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Simple bar chart implementation
        const maxValue = Math.max(...data.map(d => d.value));
        container.innerHTML = `
            <div class="simple-chart">
                ${data.map(item => `
                    <div class="chart-bar">
                        <div class="bar-fill" style="height: ${(item.value / maxValue) * 100}%"></div>
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
        // Refresh button
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadDashboardData());
        }

        // Auto-refresh toggle
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
        this.stopAutoRefresh(); // Clear existing interval
        this.refreshInterval = setInterval(() => {
            this.loadDashboardData();
        }, 30000); // Refresh every 30 seconds
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

        const container = document.querySelector('.dashboard-container') || document.body;
        container.insertBefore(errorDiv, container.firstChild);

        setTimeout(() => errorDiv.remove(), 5000);
    }

    // Utility methods
    static formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('id-ID');
    }

    static formatCurrency(amount) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardApp = new DashboardApp();
});

// Export for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DashboardApp;
}
