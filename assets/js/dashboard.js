/**
 * KOPERASI APP - Dashboard JavaScript
 * Handles dashboard functionality, metrics loading, and user interactions
 */

// Dashboard Class
class Dashboard {
    constructor() {
        this.metricsData = {};
        this.refreshInterval = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadMetrics();
        this.startAutoRefresh();
        this.initializeDateTime();
        this.initializeActivityTracking();
    }

    bindEvents() {
        // Refresh button
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshDashboard());
        }

        // Auto refresh toggle
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

        // Quick action buttons
        this.bindQuickActions();

        // Navigation
        this.bindNavigation();
    }

    bindQuickActions() {
        // Quick member registration
        const quickMemberBtn = document.getElementById('quick-member');
        if (quickMemberBtn) {
            quickMemberBtn.addEventListener('click', () => this.showQuickMemberModal());
        }

        // Quick loan application
        const quickLoanBtn = document.getElementById('quick-loan');
        if (quickLoanBtn) {
            quickLoanBtn.addEventListener('click', () => this.showQuickLoanModal());
        }
    }

    bindNavigation() {
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        console.log('🔧 Mobile menu toggle element:', mobileMenuToggle);
        
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', () => this.toggleMobileMenu());
            console.log('🔧 Mobile menu toggle event listener added');
        } else {
            console.error('🔧 Mobile menu toggle element not found!');
        }

        // Sidebar navigation
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadPage(link.dataset.href);
            });
        });
    }

    async loadMetrics() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();

        try {
            const response = await fetch('/maruba/index.php/api/dashboard/metrics', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.metricsData = data;
            this.renderMetrics(data);
            this.hideLoading();

        } catch (error) {
            console.error('Error loading metrics:', error);
            this.showError('Gagal memuat data dashboard. Silakan coba lagi.');
            this.hideLoading();
        } finally {
            this.isLoading = false;
        }
    }

    renderMetrics(data) {
        const metricsGrid = document.getElementById('metrics-grid');
        if (!metricsGrid) return;

        const metricsHTML = this.generateMetricsHTML(data);
        metricsGrid.innerHTML = metricsHTML;

        // Add animations
        this.animateMetrics();
    }

    generateMetricsHTML(data) {
        const metrics = [
            {
                label: 'Total Pinjaman',
                value: data.total_loans || 0,
                icon: 'bi-cash-stack',
                type: 'primary',
                change: data.loans_change || 0
            },
            {
                label: 'Anggota Aktif',
                value: data.active_members || 0,
                icon: 'bi-people',
                type: 'success',
                change: data.members_change || 0
            },
            {
                label: 'Pinjaman Berjalan',
                value: data.active_loans || 0,
                icon: 'bi-arrow-repeat',
                type: 'warning',
                change: data.active_loans_change || 0
            },
            {
                label: 'NPL (%)',
                value: data.npl_percentage || 0,
                icon: 'bi-exclamation-triangle',
                type: 'danger',
                change: data.npl_change || 0,
                suffix: '%'
            }
        ];

        return metrics.map(metric => `
            <div class="metric-card ${metric.type} fade-in">
                <div class="metric-icon ${metric.type}">
                    <i class="bi ${metric.icon}"></i>
                </div>
                <div class="metric-value">
                    ${this.formatNumber(metric.value)}${metric.suffix || ''}
                </div>
                <div class="metric-label">${metric.label}</div>
                ${metric.change !== undefined ? `
                    <div class="metric-change ${metric.change >= 0 ? 'positive' : 'negative'}">
                        <i class="bi bi-${metric.change >= 0 ? 'arrow-up' : 'arrow-down'}"></i>
                        ${Math.abs(metric.change).toFixed(1)}% dari bulan lalu
                    </div>
                ` : ''}
            </div>
        `).join('');
    }

    formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    animateMetrics() {
        const metricCards = document.querySelectorAll('.metric-card');
        metricCards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('fade-in');
            }, index * 100);
        });
    }

    async refreshDashboard() {
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Memperbarui...';
        }

        await this.loadMetrics();

        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh';
        }
    }

    startAutoRefresh() {
        const autoRefreshToggle = document.getElementById('auto-refresh-toggle');
        if (autoRefreshToggle && autoRefreshToggle.checked) {
            this.refreshInterval = setInterval(() => {
                this.loadMetrics();
            }, 30000); // 30 seconds
        }
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    showLoading() {
        const metricsGrid = document.getElementById('metrics-grid');
        if (metricsGrid) {
            metricsGrid.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Memuat data dashboard...
                </div>
            `;
        }
    }

    hideLoading() {
        // Loading will be hidden when metrics are rendered
    }

    showError(message) {
        const metricsGrid = document.getElementById('metrics-grid');
        if (metricsGrid) {
            metricsGrid.innerHTML = `
                <div class="error-message">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const container = document.querySelector('.dashboard-container');
        if (container) {
            container.insertBefore(notification, container.firstChild);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    }

    initializeDateTime() {
        const updateTimeDisplay = () => {
            const now = new Date();
            const timeElement = document.getElementById('time-display');
            const dateElement = document.getElementById('date-display');

            if (timeElement) {
                timeElement.textContent = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            }

            if (dateElement) {
                dateElement.textContent = now.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
        };

        updateTimeDisplay();
        setInterval(updateTimeDisplay, 1000);
    }

    initializeActivityTracking() {
        // Track user activity for session management
        let activityTimer;
        
        const resetActivityTimer = () => {
            clearTimeout(activityTimer);
            activityTimer = setTimeout(() => {
                // Check session status
                this.checkSessionStatus();
            }, 25 * 60 * 1000); // 25 minutes (before 30 min session timeout)
        };

        // Reset timer on user activity
        ['mousedown', 'keydown', 'scroll', 'click'].forEach(event => {
            document.addEventListener(event, resetActivityTimer, true);
        });

        resetActivityTimer();
    }

    async checkSessionStatus() {
        try {
            const response = await fetch('/maruba/index.php/api/session/check', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.status === 401) {
                // Session expired
                this.showSessionExpiredModal();
            }
        } catch (error) {
            console.error('Session check failed:', error);
        }
    }

    showSessionExpiredModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Session Berakhir</h5>
                    </div>
                    <div class="modal-body">
                        <p>Session Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="location.href='/maruba/index.php/'">
                            Login Kembali
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }

    toggleMobileMenu() {
        console.log('🔧 Mobile menu toggle clicked');
        const sidebar = document.getElementById('main-sidenav');
        console.log('🔧 Sidebar element:', sidebar);
        
        if (sidebar) {
            sidebar.classList.toggle('show');
            console.log('🔧 Sidebar classes after toggle:', sidebar.className);
        } else {
            console.error('🔧 Sidebar element not found!');
        }
    }

    async loadPage(href) {
        try {
            this.showLoading();
            
            const response = await fetch(href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const html = await response.text();
                const content = document.getElementById('dynamicContent');
                if (content) {
                    content.innerHTML = html;
                }
            } else {
                throw new Error('Failed to load page');
            }
        } catch (error) {
            console.error('Error loading page:', error);
            this.showError('Gagal memuat halaman. Silakan coba lagi.');
        }
    }

    showQuickMemberModal() {
        // Implementation for quick member registration modal
        console.log('Show quick member modal');
    }

    showQuickLoanModal() {
        // Implementation for quick loan application modal
        console.log('Show quick loan modal');
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be ready
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            window.dashboard = new Dashboard();
        });
    } else {
        // Fallback if jQuery is not loaded
        window.dashboard = new Dashboard();
    }
});

// Global functions for external access
window.refreshDashboard = function() {
    if (window.dashboard) {
        window.dashboard.refreshDashboard();
    }
};

window.showNotification = function(message, type = 'info') {
    if (window.dashboard) {
        window.dashboard.showNotification(message, type);
    }
};
