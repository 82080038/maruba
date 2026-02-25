<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Kelola Karyawan</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <a href="<?= route_url('index.php/payroll/employees/create') ?>" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Tambah Karyawan
                            </a>
                            <a href="<?= route_url('index.php/payroll/process') ?>" class="btn btn-success ml-2">
                                <i class="fas fa-calculator"></i> Proses Payroll
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="input-group">
                                <select class="form-control" id="statusFilter">
                                    <option value="">Semua Status</option>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Tidak Aktif</option>
                                    <option value="terminated">Diberhentikan</option>
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" onclick="filterByStatus()">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4 id="totalEmployees">0</h4>
                                    <small>Total Karyawan</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4 id="activeEmployees">0</h4>
                                    <small>Aktif</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4 id="inactiveEmployees">0</h4>
                                    <small>Tidak Aktif</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h4 id="terminatedEmployees">0</h4>
                                    <small>Diberhentikan</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID Karyawan</th>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                    <th>Departemen</th>
                                    <th>Gaji Pokok</th>
                                    <th>Status</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="employeesTable">
                                <!-- Employee data loaded via AJAX -->
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="spinner-border" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                        <p>Loading employees...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        <nav aria-label="Employee pagination">
                            <ul class="pagination" id="pagination">
                                <!-- Pagination links generated by JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Details Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Karyawan</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="employeeDetails">
                <!-- Employee details loaded via AJAX -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load employees data
let currentPage = 1;
let currentStatus = '';

function loadEmployees(page = 1, status = '') {
    currentPage = page;
    currentStatus = status;

    fetch(`<?= route_url('index.php/api/payroll/employees') ?>?page=${page}&status=${status}`)
        .then(response => response.json())
        .then(data => {
            updateEmployeeTable(data.employees);
            updatePagination(data.pagination);
            updateStatistics(data.statistics);
        })
        .catch(error => {
            console.error('Error loading employees:', error);
            document.getElementById('employeesTable').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Gagal memuat data karyawan
                    </td>
                </tr>
            `;
        });
}

function updateEmployeeTable(employees) {
    const tbody = document.getElementById('employeesTable');

    if (!employees || employees.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted">
                    <i class="fas fa-users fa-2x mb-2"></i><br>
                    Tidak ada data karyawan
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = employees.map(employee => `
        <tr>
            <td><strong>${employee.employee_number}</strong></td>
            <td>${employee.name}</td>
            <td>${employee.position}</td>
            <td>${employee.department || '-'}</td>
            <td>Rp ${new Intl.NumberFormat('id-ID').format(employee.basic_salary)}</td>
            <td>
                <span class="badge badge-${getStatusBadge(employee.status)}">
                    ${getStatusText(employee.status)}
                </span>
            </td>
            <td>${new Date(employee.join_date).toLocaleDateString('id-ID')}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" onclick="viewEmployee(${employee.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-primary" onclick="editEmployee(${employee.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-success" onclick="payrollEmployee(${employee.id})">
                        <i class="fas fa-money-bill-wave"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function updatePagination(pagination) {
    const paginationEl = document.getElementById('pagination');
    if (!pagination || pagination.total_pages <= 1) {
        paginationEl.innerHTML = '';
        return;
    }

    let html = '';

    // Previous button
    if (pagination.current_page > 1) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadEmployees(${pagination.current_page - 1}, '${currentStatus}')">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>`;
    }

    // Page numbers
    for (let i = Math.max(1, pagination.current_page - 2);
         i <= Math.min(pagination.total_pages, pagination.current_page + 2);
         i++) {
        html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadEmployees(${i}, '${currentStatus}')">${i}</a>
        </li>`;
    }

    // Next button
    if (pagination.current_page < pagination.total_pages) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadEmployees(${pagination.current_page + 1}, '${currentStatus}')">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>`;
    }

    paginationEl.innerHTML = html;
}

function updateStatistics(statistics) {
    document.getElementById('totalEmployees').textContent = statistics.total || 0;
    document.getElementById('activeEmployees').textContent = statistics.active || 0;
    document.getElementById('inactiveEmployees').textContent = statistics.inactive || 0;
    document.getElementById('terminatedEmployees').textContent = statistics.terminated || 0;
}

function filterByStatus() {
    const status = document.getElementById('statusFilter').value;
    loadEmployees(1, status);
}

function viewEmployee(employeeId) {
    // Load employee details via AJAX
    fetch(`<?= route_url('index.php/api/payroll/employees') ?>/${employeeId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('employeeDetails').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi Personal</h6>
                        <table class="table table-sm">
                            <tr><td>ID Karyawan:</td><td><strong>${data.employee_number}</strong></td></tr>
                            <tr><td>Nama:</td><td>${data.name}</td></tr>
                            <tr><td>Jabatan:</td><td>${data.position}</td></tr>
                            <tr><td>Departemen:</td><td>${data.department || '-'}</td></tr>
                            <tr><td>Tanggal Masuk:</td><td>${new Date(data.join_date).toLocaleDateString('id-ID')}</td></tr>
                            <tr><td>Status:</td><td><span class="badge badge-${getStatusBadge(data.status)}">${getStatusText(data.status)}</span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi Gaji & Keuangan</h6>
                        <table class="table table-sm">
                            <tr><td>Gaji Pokok:</td><td>Rp ${new Intl.NumberFormat('id-ID').format(data.basic_salary)}</td></tr>
                            <tr><td>Rekening Bank:</td><td>${data.bank_name || '-'} - ${data.bank_account || '-'}</td></tr>
                            <tr><td>NPWP:</td><td>${data.tax_id || '-'}</td></tr>
                        </table>
                        ${data.allowances ? `
                        <h6>Tunjangan</h6>
                        <small class="text-muted">Data tunjangan dalam format JSON</small>
                        ` : ''}
                    </div>
                </div>
            `;
            $('#employeeModal').modal('show');
        })
        .catch(error => {
            console.error('Error loading employee details:', error);
            alert('Gagal memuat detail karyawan');
        });
}

function editEmployee(employeeId) {
    window.location.href = `<?= route_url('index.php/payroll/employees') ?>/edit/${employeeId}`;
}

function payrollEmployee(employeeId) {
    window.location.href = `<?= route_url('index.php/payroll/process') ?>?employee_id=${employeeId}`;
}

// Helper functions
function getStatusBadge(status) {
    return {
        'active': 'success',
        'inactive': 'warning',
        'terminated': 'danger'
    }[status] || 'secondary';
}

function getStatusText(status) {
    return {
        'active': 'Aktif',
        'inactive': 'Tidak Aktif',
        'terminated': 'Diberhentikan'
    }[status] || status;
}

// Load initial data
document.addEventListener('DOMContentLoaded', function() {
    loadEmployees();
});
</script>

<?php include view_path('layout/footer'); ?>
