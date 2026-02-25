<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Manajemen Langganan</h4>
                </div>
                <div class="card-body">
                    <!-- Current Subscription Info -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">Paket Langganan Saat Ini</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h4 id="currentPlan">Professional</h4>
                                                <small class="text-muted">Paket Aktif</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h4>Rp <span id="monthlyCost">1,500,000</span></h4>
                                                <small class="text-muted">Biaya Bulanan</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h4 id="maxUsers">10</h4>
                                                <small class="text-muted">Max Pengguna</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h4 id="maxMembers">1000</h4>
                                                <small class="text-muted">Max Anggota</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <strong>Status:</strong> <span class="badge badge-success" id="subscriptionStatus">Aktif</span>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <strong>Berakhir:</strong> <span id="expiresAt">2026-02-22</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Available Plans -->
                        <div class="col-md-8">
                            <h5>Paket yang Tersedia</h5>
                            <div class="row" id="plansContainer">
                                <!-- Plans loaded via AJAX -->
                                <div class="col-12 text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p>Loading subscription plans...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Billing History -->
                        <div class="col-md-4">
                            <h5>Riwayat Tagihan</h5>
                            <div class="card">
                                <div class="card-body" id="billingHistory">
                                    <!-- Billing history loaded via AJAX -->
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Usage Statistics -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Penggunaan Saat Ini</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small>Pengguna Aktif</small>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: 70%" id="usersUsage"></div>
                                        </div>
                                        <small class="text-muted"><span id="currentUsers">7</span> / <span id="maxUsersDisplay">10</span> pengguna</small>
                                    </div>
                                    <div class="mb-2">
                                        <small>Data Anggota</small>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 30%" id="membersUsage"></div>
                                        </div>
                                        <small class="text-muted"><span id="currentMembers">300</span> / <span id="maxMembersDisplay">1000</span> anggota</small>
                                    </div>
                                    <div>
                                        <small>Penyimpanan</small>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 45%" id="storageUsage"></div>
                                        </div>
                                        <small class="text-muted"><span id="currentStorage">2.1</span> / <span id="maxStorageDisplay">5</span> GB</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upgrade Modal -->
<div class="modal fade" id="upgradeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upgrade Paket Langganan</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="upgradeModalBody">
                <!-- Upgrade form loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
// Load subscription data
document.addEventListener('DOMContentLoaded', function() {
    loadSubscriptionInfo();
    loadAvailablePlans();
    loadBillingHistory();
    loadUsageStatistics();
});

function loadSubscriptionInfo() {
    fetch('<?= route_url('index.php/api/subscription/current') ?>')
        .then(response => response.json())
        .then(data => {
            document.getElementById('currentPlan').textContent = data.plan_name;
            document.getElementById('monthlyCost').textContent = new Intl.NumberFormat('id-ID').format(data.monthly_cost);
            document.getElementById('maxUsers').textContent = data.max_users;
            document.getElementById('maxMembers').textContent = data.max_members;
            document.getElementById('subscriptionStatus').textContent = data.status === 'active' ? 'Aktif' : 'Tidak Aktif';
            document.getElementById('expiresAt').textContent = new Date(data.expires_at).toLocaleDateString('id-ID');
        })
        .catch(error => {
            console.error('Error loading subscription info:', error);
        });
}

function loadAvailablePlans() {
    fetch('<?= route_url('index.php/api/subscription/plans') ?>')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('plansContainer');
            container.innerHTML = data.plans.map(plan => `
                <div class="col-md-4 mb-3">
                    <div class="card h-100 ${plan.name === 'professional' ? 'border-primary' : ''}">
                        <div class="card-header ${plan.name === 'professional' ? 'bg-primary text-white' : 'bg-light'}">
                            <h6 class="card-title mb-0">${plan.display_name}</h6>
                            ${plan.name === 'professional' ? '<small class="text-white-50">Paket Saat Ini</small>' : ''}
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <h4 class="text-primary">Rp ${new Intl.NumberFormat('id-ID').format(plan.price_monthly)}</h4>
                                <small class="text-muted">per bulan</small>
                            </div>
                            <ul class="list-unstyled small mb-3">
                                <li><i class="fas fa-users"></i> Max ${plan.max_users} pengguna</li>
                                <li><i class="fas fa-user-friends"></i> Max ${plan.max_members} anggota</li>
                                <li><i class="fas fa-hdd"></i> ${plan.max_storage_gb} GB penyimpanan</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            ${plan.name === 'professional' ?
                                '<button class="btn btn-primary btn-sm btn-block" disabled>Paket Saat Ini</button>' :
                                `<button class="btn btn-outline-primary btn-sm btn-block" onclick="upgradeToPlan('${plan.name}')">Upgrade</button>`
                            }
                        </div>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading plans:', error);
            document.getElementById('plansContainer').innerHTML = '<div class="col-12 text-center text-danger">Gagal memuat data paket</div>';
        });
}

function loadBillingHistory() {
    fetch('<?= route_url('index.php/api/subscription/billing') ?>')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('billingHistory');
            if (!data.billings || data.billings.length === 0) {
                container.innerHTML = '<p class="text-muted mb-0">Belum ada riwayat tagihan</p>';
                return;
            }

            container.innerHTML = data.billings.slice(0, 5).map(billing => `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <small class="text-muted">${new Date(billing.billing_period_start).toLocaleDateString('id-ID')} - ${new Date(billing.billing_period_end).toLocaleDateString('id-ID')}</small>
                        <br>
                        <small>Rp ${new Intl.NumberFormat('id-ID').format(billing.amount)}</small>
                    </div>
                    <span class="badge badge-${billing.status === 'paid' ? 'success' : 'warning'}">${billing.status}</span>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading billing history:', error);
            document.getElementById('billingHistory').innerHTML = '<p class="text-danger">Gagal memuat riwayat tagihan</p>';
        });
}

function loadUsageStatistics() {
    fetch('<?= route_url('index.php/api/subscription/usage') ?>')
        .then(response => response.json())
        .then(data => {
            // Update usage bars
            const usersPercent = (data.current_users / data.max_users) * 100;
            const membersPercent = (data.current_members / data.max_members) * 100;
            const storagePercent = (data.current_storage_gb / data.max_storage_gb) * 100;

            document.getElementById('usersUsage').style.width = usersPercent + '%';
            document.getElementById('membersUsage').style.width = membersPercent + '%';
            document.getElementById('storageUsage').style.width = storagePercent + '%';

            // Update text values
            document.getElementById('currentUsers').textContent = data.current_users;
            document.getElementById('maxUsersDisplay').textContent = data.max_users;
            document.getElementById('currentMembers').textContent = data.current_members;
            document.getElementById('maxMembersDisplay').textContent = data.max_members;
            document.getElementById('currentStorage').textContent = data.current_storage_gb.toFixed(1);
            document.getElementById('maxStorageDisplay').textContent = data.max_storage_gb;
        })
        .catch(error => {
            console.error('Error loading usage statistics:', error);
        });
}

function upgradeToPlan(planName) {
    fetch(`<?= route_url('index.php/api/subscription/plans') ?>/${planName}`)
        .then(response => response.json())
        .then(plan => {
            document.getElementById('upgradeModalBody').innerHTML = `
                <div class="text-center mb-4">
                    <h5>Upgrade ke ${plan.display_name}</h5>
                    <p class="text-muted">Konfirmasi upgrade paket langganan</p>
                </div>

                <div class="row text-center mb-4">
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <small class="text-muted">Pengguna</small>
                            <br><strong>${plan.max_users}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <small class="text-muted">Anggota</small>
                            <br><strong>${plan.max_members}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2">
                            <small class="text-muted">Penyimpanan</small>
                            <br><strong>${plan.max_storage_gb} GB</strong>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <strong>Biaya: Rp ${new Intl.NumberFormat('id-ID').format(plan.price_monthly)}/bulan</strong>
                    <br>
                    <small>Upgrade akan berlaku mulai billing period berikutnya</small>
                </div>

                <form method="POST" action="<?= route_url('index.php/subscription/upgrade') ?>">
                    <input type="hidden" name="plan_name" value="${plan.name}">
                    <div class="text-center">
                        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Konfirmasi Upgrade</button>
                    </div>
                </form>
            `;
            $('#upgradeModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading plan details:', error);
            alert('Gagal memuat detail paket');
        });
}
</script>

<?php include view_path('layout/footer'); ?>
