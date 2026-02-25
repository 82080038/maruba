<?php
// Bootstrap Component Templates for KSP Application
// Reusable UI components for consistent design
?>

<script>
// =========================================
// BOOTSTRAP COMPONENT TEMPLATES
// =========================================

KSP.Components = {
    // =========================================
    // MODAL TEMPLATES
    // =========================================

    /**
     * Create member registration modal
     */
    createMemberModal: function(memberData = null) {
        const isEdit = !!memberData;
        const title = isEdit ? 'Edit Anggota' : 'Tambah Anggota Baru';

        const content = '<form id="memberForm" data-validate="true">' +
            '<input type="hidden" name="csrf_token" value="' + KSP.Config.csrfToken + '">' +
            (isEdit ? '<input type="hidden" name="id" value="' + memberData.id + '">' : '') +

            '<div class="row">' +
                '<div class="col-md-6">' +
                    '<div class="mb-3">' +
                        '<label for="member_name" class="form-label">Nama Lengkap *</label>' +
                        '<input type="text" class="form-control" id="member_name" name="name" value="' + (memberData?.name || '') + '" required>' +
                        '<div class="invalid-feedback">Nama lengkap harus diisi.</div>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="mb-3">' +
                        '<label for="member_nik" class="form-label">NIK *</label>' +
                        '<input type="text" class="form-control" id="member_nik" name="nik" value="' + (memberData?.nik || '') + '" pattern="[0-9]{16}" required>' +
                        '<div class="invalid-feedback">NIK harus 16 digit angka.</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="row">' +
                '<div class="col-md-6">' +
                    '<div class="mb-3">' +
                        '<label for="member_phone" class="form-label">Nomor Telepon *</label>' +
                        '<input type="tel" class="form-control" id="member_phone" name="phone" value="' + (memberData?.phone || '') + '" required>' +
                        '<div class="invalid-feedback">Nomor telepon harus diisi.</div>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="mb-3">' +
                        '<label for="member_email" class="form-label">Email</label>' +
                        '<input type="email" class="form-control" id="member_email" name="email" value="' + (memberData?.email || '') + '">' +
                        '<div class="invalid-feedback">Format email tidak valid.</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="mb-3">' +
                '<label for="member_address" class="form-label">Alamat Lengkap *</label>' +
                '<textarea class="form-control" id="member_address" name="address" rows="3" required>' + (memberData?.address || '') + '</textarea>' +
                '<div class="invalid-feedback">Alamat lengkap harus diisi.</div>' +
            '</div>' +

            '<div class="row">' +
                '<div class="col-md-4">' +
                    '<div class="mb-3">' +
                        '<label for="member_province" class="form-label">Provinsi *</label>' +
                        '<select class="form-select" id="member_province" name="province" required>' +
                            '<option value="">Pilih Provinsi</option>' +
                            '<option value="Jawa Barat"' + (memberData?.province === 'Jawa Barat' ? ' selected' : '') + '>Jawa Barat</option>' +
                            '<option value="Jawa Tengah"' + (memberData?.province === 'Jawa Tengah' ? ' selected' : '') + '>Jawa Tengah</option>' +
                            '<option value="Jawa Timur"' + (memberData?.province === 'Jawa Timur' ? ' selected' : '') + '>Jawa Timur</option>' +
                            '<option value="DKI Jakarta"' + (memberData?.province === 'DKI Jakarta' ? ' selected' : '') + '>DKI Jakarta</option>' +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                    '<div class="mb-3">' +
                        '<label for="member_city" class="form-label">Kota/Kabupaten *</label>' +
                        '<input type="text" class="form-control" id="member_city" name="city" value="' + (memberData?.city || '') + '" required>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                    '<div class="mb-3">' +
                        '<label for="member_birth_date" class="form-label">Tanggal Lahir *</label>' +
                        '<input type="date" class="form-control" id="member_birth_date" name="birth_date" value="' + (memberData?.birth_date || '') + '" required>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="row">' +
                '<div class="col-md-6">' +
                    '<div class="mb-3">' +
                        '<label for="member_gender" class="form-label">Jenis Kelamin *</label>' +
                        '<select class="form-select" id="member_gender" name="gender" required>' +
                            '<option value="">Pilih Jenis Kelamin</option>' +
                            '<option value="L"' + (memberData?.gender === 'L' ? ' selected' : '') + '>Laki-laki</option>' +
                            '<option value="P"' + (memberData?.gender === 'P' ? ' selected' : '') + '>Perempuan</option>' +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="mb-3">' +
                        '<label for="member_income" class="form-label">Pendapatan Bulanan</label>' +
                        '<input type="number" class="form-control" id="member_income" name="monthly_income" value="' + (memberData?.monthly_income || '') + '" placeholder="0">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</form>';

        const buttons = [
            {
                text: 'Batal',
                class: 'btn-secondary',
                id: 'cancelBtn',
                onClick: function() {
                    bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();
                }
            },
            {
                text: isEdit ? 'Update Anggota' : 'Simpan Anggota',
                class: 'btn-primary',
                id: 'saveBtn',
                onClick: function() {
                    KSP.Components.saveMember(isEdit);
                }
            }
        ];

        const modal = KSP.UI.createModal({
            id: 'memberModal',
            title: title,
            size: 'lg',
            content: content,
            buttons: buttons,
            onShow: function() {
                // Initialize form validation
                KSP.UI.initializeFormValidation();
            }
        });

        modal.show();
    },

    /**
     * Save member data
     */
    saveMember: function(isEdit = false) {
        const form = document.getElementById('memberForm');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const formData = KSP.Form.serializeObject(form);
        const url = isEdit ?
            `${KSP.Config.apiUrl}/members/update` :
            `${KSP.Config.apiUrl}/members/create`;

        KSP.Ajax.post(url, formData, function(response) {
            KSP.UI.showToast('Anggota berhasil disimpan!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();

            // Reload members list if on members page
            if (window.location.href.includes('/members')) {
                KSP.Nav.loadPage(window.location.href);
            }
        });
    },

    /**
     * Create loan application modal
     */
    createLoanModal: function(loanData = null) {
        const isEdit = !!loanData;
        const title = isEdit ? 'Edit Pengajuan Pinjaman' : 'Pengajuan Pinjaman Baru';

        const content = `
            <form id="loanForm" data-validate="true">
                <input type="hidden" name="csrf_token" value="${KSP.Config.csrfToken}">
                ${isEdit ? `<input type="hidden" name="id" value="${loanData.id}">` : ''}

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="loan_member" class="form-label">Anggota *</label>
                            <select class="form-select" id="loan_member" name="member_id" required>
                                <option value="">Pilih Anggota</option>
                                <!-- Options will be loaded via AJAX -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="loan_product" class="form-label">Produk Pinjaman *</label>
                            <select class="form-select" id="loan_product" name="product_id" required>
                                <option value="">Pilih Produk</option>
                                <!-- Options will be loaded via AJAX -->
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="loan_amount" class="form-label">Jumlah Pinjaman *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="loan_amount" name="amount"
                                       value="${loanData?.amount || ''}" min="1000000" required>
                            </div>
                            <div class="invalid-feedback">Jumlah pinjaman minimal Rp 1.000.000.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="loan_tenor" class="form-label">Tenor (Bulan) *</label>
                            <select class="form-select" id="loan_tenor" name="tenor_months" required>
                                <option value="">Pilih Tenor</option>
                                <option value="6" ${loanData?.tenor_months == 6 ? 'selected' : ''}>6 Bulan</option>
                                <option value="12" ${loanData?.tenor_months == 12 ? 'selected' : ''}>12 Bulan</option>
                                <option value="18" ${loanData?.tenor_months == 18 ? 'selected' : ''}>18 Bulan</option>
                                <option value="24" ${loanData?.tenor_months == 24 ? 'selected' : ''}>24 Bulan</option>
                                <option value="36" ${loanData?.tenor_months == 36 ? 'selected' : ''}>36 Bulan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="loan_purpose" class="form-label">Tujuan Pinjaman *</label>
                    <textarea class="form-control" id="loan_purpose" name="purpose" rows="3" required>${loanData?.purpose || ''}</textarea>
                    <div class="invalid-feedback">Tujuan pinjaman harus diisi.</div>
                </div>

                <div id="loanCalculation" class="alert alert-info" style="display: none;">
                    <h6>Perhitungan Pinjaman:</h6>
                    <div id="calculationDetails"></div>
                </div>
            </form>
        `;

        const buttons = [
            {
                text: 'Batal',
                class: 'btn-secondary',
                id: 'cancelLoanBtn',
                onClick: function() {
                    bootstrap.Modal.getInstance(document.getElementById('loanModal')).hide();
                }
            },
            {
                text: isEdit ? 'Update Pengajuan' : 'Ajukan Pinjaman',
                class: 'btn-primary',
                id: 'saveLoanBtn',
                onClick: function() {
                    KSP.Components.saveLoan(isEdit);
                }
            }
        ];

        const modal = KSP.UI.createModal({
            id: 'loanModal',
            title: title,
            size: 'lg',
            content: content,
            buttons: buttons,
            onShow: function() {
                KSP.Components.loadLoanFormData();
                KSP.UI.initializeFormValidation();

                // Add calculation on amount/tenor change
                $('#loan_amount, #loan_product, #loan_tenor').on('change', function() {
                    KSP.Components.calculateLoanPreview();
                });
            }
        });

        modal.show();
    },

    /**
     * Load loan form data (members and products)
     */
    loadLoanFormData: function() {
        // Load members
        KSP.Ajax.get(`${KSP.Config.apiUrl}/members/list`, {}, function(response) {
            if (response.success && response.data) {
                let options = '<option value="">Pilih Anggota</option>';
                response.data.forEach(member => {
                    options += `<option value="${member.id}">${member.name} (${member.nik})</option>`;
                });
                $('#loan_member').html(options);
            }
        });

        // Load loan products
        KSP.Ajax.get(`${KSP.Config.apiUrl}/loan-products`, {}, function(response) {
            if (response.success && response.data) {
                let options = '<option value="">Pilih Produk</option>';
                response.data.forEach(product => {
                    options += `<option value="${product.id}">${product.name}</option>`;
                });
                $('#loan_product').html(options);
            }
        });
    },

    /**
     * Calculate loan preview
     */
    calculateLoanPreview: function() {
        const amount = parseFloat($('#loan_amount').val());
        const tenor = parseInt($('#loan_tenor').val());
        const productId = $('#loan_product').val();

        if (!amount || !tenor || !productId) {
            $('#loanCalculation').hide();
            return;
        }

        KSP.Ajax.get(`${KSP.Config.apiUrl}/loan-products/${productId}`, {}, function(response) {
            if (response.success && response.data) {
                const product = response.data;
                const totalAmount = amount + (amount * product.interest_rate * tenor / 1200);
                const monthlyInstallment = totalAmount / tenor;

                const calculationHtml = `
                    <div class="row">
                        <div class="col-6"><strong>Pokok:</strong></div>
                        <div class="col-6">Rp ${KSP.Utils.formatCurrency(amount)}</div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Bunga (${product.interest_rate}%):</strong></div>
                        <div class="col-6">Rp ${KSP.Utils.formatCurrency(amount * product.interest_rate * tenor / 1200)}</div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Total Pinjaman:</strong></div>
                        <div class="col-6"><strong>Rp ${KSP.Utils.formatCurrency(totalAmount)}</strong></div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Angsuran/Bulan:</strong></div>
                        <div class="col-6"><strong>Rp ${KSP.Utils.formatCurrency(monthlyInstallment)}</strong></div>
                    </div>
                `;

                $('#calculationDetails').html(calculationHtml);
                $('#loanCalculation').show();
            }
        });
    },

    /**
     * Save loan data
     */
    saveLoan: function(isEdit = false) {
        const form = document.getElementById('loanForm');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const formData = KSP.Form.serializeObject(form);
        const url = isEdit ?
            `${KSP.Config.apiUrl}/loans/update` :
            `${KSP.Config.apiUrl}/loans/create`;

        KSP.Ajax.post(url, formData, function(response) {
            KSP.UI.showToast('Pengajuan pinjaman berhasil dikirim!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('loanModal')).hide();

            // Reload loans list if on loans page
            if (window.location.href.includes('/loans')) {
                KSP.Nav.loadPage(window.location.href);
            }
        });
    },

    // =========================================
    // DATA TABLE TEMPLATES
    // =========================================

    /**
     * Create members data table
     */
    createMembersTable: function(containerId, options = {}) {
        const defaults = {
            ajax: {
                url: `${KSP.Config.apiUrl}/members/list`,
                type: 'GET'
            },
            columns: [
                {
                    title: 'No',
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                { title: 'Nama', data: 'name' },
                { title: 'NIK', data: 'nik' },
                { title: 'Telepon', data: 'phone' },
                { title: 'Status', data: 'status',
                  render: function(data) {
                      const badges = {
                          'active': 'success',
                          'inactive': 'secondary',
                          'pending': 'warning',
                          'rejected': 'danger'
                      };
                      return `<span class="badge bg-${badges[data] || 'secondary'}">${data}</span>`;
                  }
                },
                { title: 'Bergabung', data: 'created_at',
                  render: function(data) {
                      return KSP.Utils.formatDate(data);
                  }
                },
                { title: 'Aksi',
                  render: function(data, type, row) {
                      return `
                          <button class="btn btn-sm btn-outline-primary me-1" onclick="KSP.Components.viewMember(${row.id})">
                              <i class="bi bi-eye"></i>
                          </button>
                          <button class="btn btn-sm btn-outline-warning me-1" onclick="KSP.Components.editMember(${row.id})">
                              <i class="bi bi-pencil"></i>
                          </button>
                          <button class="btn btn-sm btn-outline-danger" onclick="KSP.Components.deleteMember(${row.id}, '${row.name}')">
                              <i class="bi bi-trash"></i>
                          </button>
                      `;
                  },
                  orderable: false
                }
            ],
            responsive: true,
            pageLength: 25
        };

        const config = $.extend(true, {}, defaults, options);
        return KSP.UI.createDataTable(`#${containerId}`, config);
    },

    /**
     * Create loans data table
     */
    createLoansTable: function(containerId, options = {}) {
        const defaults = {
            ajax: {
                url: `${KSP.Config.apiUrl}/loans/list`,
                type: 'GET'
            },
            columns: [
                {
                    title: 'No',
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                { title: 'No. Pinjaman', data: 'loan_number' },
                { title: 'Anggota', data: 'member_name' },
                { title: 'Jumlah',
                  data: 'principal_amount',
                  render: function(data) {
                      return KSP.Utils.formatCurrency(data);
                  }
                },
                { title: 'Tenor', data: 'tenor_months',
                  render: function(data) {
                      return `${data} bulan`;
                  }
                },
                { title: 'Status', data: 'status',
                  render: function(data) {
                      const badges = {
                          'draft': 'secondary',
                          'submitted': 'info',
                          'survey_pending': 'warning',
                          'survey_completed': 'primary',
                          'approved': 'success',
                          'disbursed': 'success',
                          'active': 'success',
                          'rejected': 'danger',
                          'defaulted': 'danger'
                      };
                      return `<span class="badge bg-${badges[data] || 'secondary'}">${data}</span>`;
                  }
                },
                { title: 'Tanggal Pengajuan', data: 'application_date',
                  render: function(data) {
                      return KSP.Utils.formatDate(data);
                  }
                },
                { title: 'Aksi',
                  render: function(data, type, row) {
                      let actions = `
                          <button class="btn btn-sm btn-outline-primary me-1" onclick="KSP.Components.viewLoan(${row.id})">
                              <i class="bi bi-eye"></i>
                          </button>
                      `;

                      if (row.status === 'draft' || row.status === 'survey_pending') {
                          actions += `
                              <button class="btn btn-sm btn-outline-warning me-1" onclick="KSP.Components.editLoan(${row.id})">
                                  <i class="bi bi-pencil"></i>
                              </button>
                          `;
                      }

                      if (KSP.Auth.hasPermission('loans.approve') && row.status === 'survey_completed') {
                          actions += `
                              <button class="btn btn-sm btn-outline-success me-1" onclick="KSP.Components.approveLoan(${row.id})">
                                  <i class="bi bi-check-circle"></i>
                              </button>
                              <button class="btn btn-sm btn-outline-danger" onclick="KSP.Components.rejectLoan(${row.id})">
                                  <i class="bi bi-x-circle"></i>
                              </button>
                          `;
                      }

                      return actions;
                  },
                  orderable: false
                }
            ],
            responsive: true,
            pageLength: 25
        };

        const config = $.extend(true, {}, defaults, options);
        return KSP.UI.createDataTable(`#${containerId}`, config);
    },

    // =========================================
    // DASHBOARD COMPONENTS
    // =========================================

    /**
     * Create dashboard metrics cards
     */
    createDashboardMetrics: function(metrics) {
        const cards = [
            {
                icon: 'bi-people-fill',
                title: 'Total Anggota',
                content: metrics.total_members || 0,
                class: 'bg-primary'
            },
            {
                icon: 'bi-cash-stack',
                title: 'Pinjaman Aktif',
                content: metrics.active_loans || 0,
                class: 'bg-success'
            },
            {
                icon: 'bi-currency-dollar',
                title: 'Outstanding',
                content: KSP.Utils.formatCurrency(metrics.total_outstanding || 0),
                class: 'bg-warning'
            },
            {
                icon: 'bi-exclamation-triangle-fill',
                title: 'NPL Ratio',
                content: `${metrics.npl_ratio || 0}%`,
                class: metrics.npl_ratio > 5 ? 'bg-danger' : 'bg-info'
            }
        ];

        return KSP.UI.createCardGrid(cards, 4);
    },

    /**
     * Create recent activities list
     */
    createRecentActivities: function(activities) {
        if (!activities || activities.length === 0) {
            return '<p class="text-muted">Tidak ada aktivitas terkini.</p>';
        }

        let html = '';
        activities.forEach(activity => {
            html += `
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.5rem;"></i>
                    <div class="flex-grow-1">
                        <strong>${activity.user_name || 'System'}</strong>
                        ${activity.action} ${activity.entity} #${activity.entity_id}
                        <br><small class="text-muted">${KSP.Utils.formatDateTime(activity.created_at)}</small>
                    </div>
                </div>
            `;
        });

        return html;
    },

    // =========================================
    // ACTION HANDLERS
    // =========================================

    /**
     * View member details
     */
    viewMember: function(memberId) {
        KSP.Ajax.get(`${KSP.Config.apiUrl}/members/detail`, { id: memberId }, function(response) {
            if (response.success && response.data) {
                const member = response.data;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informasi Pribadi</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Nama:</strong></td><td>${member.name}</td></tr>
                                <tr><td><strong>NIK:</strong></td><td>${member.nik}</td></tr>
                                <tr><td><strong>Telepon:</strong></td><td>${member.phone}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${member.email || '-'}</td></tr>
                                <tr><td><strong>Tanggal Lahir:</strong></td><td>${KSP.Utils.formatDate(member.birth_date)}</td></tr>
                                <tr><td><strong>Jenis Kelamin:</strong></td><td>${member.gender === 'L' ? 'Laki-laki' : 'Perempuan'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Informasi Alamat</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Alamat:</strong></td><td>${member.address}</td></tr>
                                <tr><td><strong>Kota:</strong></td><td>${member.city}</td></tr>
                                <tr><td><strong>Provinsi:</strong></td><td>${member.province}</td></tr>
                                <tr><td><strong>Kode Pos:</strong></td><td>${member.postal_code || '-'}</td></tr>
                            </table>
                        </div>
                    </div>
                `;

                KSP.UI.createModal({
                    id: 'viewMemberModal',
                    title: `Detail Anggota - ${member.name}`,
                    size: 'lg',
                    content: content
                }).show();
            }
        });
    },

    /**
     * Edit member
     */
    editMember: function(memberId) {
        KSP.Ajax.get(`${KSP.Config.apiUrl}/members/detail`, { id: memberId }, function(response) {
            if (response.success && response.data) {
                KSP.Components.createMemberModal(response.data);
            }
        });
    },

    /**
     * Delete member
     */
    deleteMember: function(memberId, memberName) {
        KSP.UI.showConfirm(
            'Hapus Anggota',
            `Apakah Anda yakin ingin menghapus anggota "${memberName}"? Tindakan ini tidak dapat dibatalkan.`,
            function() {
                KSP.Ajax.delete(`${KSP.Config.apiUrl}/members/delete`, { id: memberId }, function(response) {
                    KSP.UI.showToast('Anggota berhasil dihapus!', 'success');
                    // Reload current page
                    KSP.Nav.loadPage(window.location.href);
                });
            }
        );
    },

    /**
     * View loan details
     */
    viewLoan: function(loanId) {
        KSP.Ajax.get(`${KSP.Config.apiUrl}/loans/detail`, { id: loanId }, function(response) {
            if (response.success && response.data) {
                const loan = response.data;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informasi Pinjaman</h6>
                            <table class="table table-sm">
                                <tr><td><strong>No. Pinjaman:</strong></td><td>${loan.loan_number}</td></tr>
                                <tr><td><strong>Anggota:</strong></td><td>${loan.member_name}</td></tr>
                                <tr><td><strong>Jumlah Pokok:</strong></td><td>${KSP.Utils.formatCurrency(loan.principal_amount)}</td></tr>
                                <tr><td><strong>Bunga:</strong></td><td>${loan.interest_rate}%</td></tr>
                                <tr><td><strong>Tenor:</strong></td><td>${loan.tenor_months} bulan</td></tr>
                                <tr><td><strong>Angsuran:</strong></td><td>${KSP.Utils.formatCurrency(loan.monthly_installment)}</td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-success">${loan.status}</span></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Informasi Tambahan</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Tujuan:</strong></td><td>${loan.purpose}</td></tr>
                                <tr><td><strong>Tanggal Pengajuan:</strong></td><td>${KSP.Utils.formatDate(loan.application_date)}</td></tr>
                                <tr><td><strong>Tanggal Disetujui:</strong></td><td>${loan.approval_date ? KSP.Utils.formatDate(loan.approval_date) : '-'}</td></tr>
                                <tr><td><strong>Tanggal Pencairan:</strong></td><td>${loan.disbursement_date ? KSP.Utils.formatDate(loan.disbursement_date) : '-'}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6>Jadwal Angsuran</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Angsuran</th>
                                        <th>Tanggal Jatuh Tempo</th>
                                        <th>Pokok</th>
                                        <th>Bunga</th>
                                        <th>Total</th>
                                        <th>Sisa</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="repaymentSchedule">
                                    <tr><td colspan="7" class="text-center">Memuat jadwal...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;

                const modal = KSP.UI.createModal({
                    id: 'viewLoanModal',
                    title: `Detail Pinjaman - ${loan.loan_number}`,
                    size: 'xl',
                    content: content,
                    onShow: function() {
                        // Load repayment schedule
                        KSP.Ajax.get(`${KSP.Config.apiUrl}/loans/repayment-schedule`, { loan_id: loanId }, function(response) {
                            if (response.success && response.data) {
                                let scheduleHtml = '';
                                response.data.forEach(repayment => {
                                    scheduleHtml += `
                                        <tr>
                                            <td>${repayment.installment_number}</td>
                                            <td>${KSP.Utils.formatDate(repayment.due_date)}</td>
                                            <td>${KSP.Utils.formatCurrency(repayment.principal_payment)}</td>
                                            <td>${KSP.Utils.formatCurrency(repayment.interest_payment)}</td>
                                            <td>${KSP.Utils.formatCurrency(repayment.total_payment)}</td>
                                            <td>${KSP.Utils.formatCurrency(repayment.remaining_balance)}</td>
                                            <td><span class="badge bg-${repayment.status === 'paid' ? 'success' : 'warning'}">${repayment.status}</span></td>
                                        </tr>
                                    `;
                                });
                                $('#repaymentSchedule').html(scheduleHtml);
                            }
                        });
                    }
                });

                modal.show();
            }
        });
    }
};

// =========================================
// GLOBAL FUNCTIONS FOR EASY ACCESS
// =========================================

function showCreateMemberModal() {
    KSP.Components.createMemberModal();
}

function showCreateLoanModal() {
    KSP.Components.createLoanModal();
}

function viewMember(id) {
    KSP.Components.viewMember(id);
}

function editMember(id) {
    KSP.Components.editMember(id);
}

function deleteMember(id, name) {
    KSP.Components.deleteMember(id, name);
}

function viewLoan(id) {
    KSP.Components.viewLoan(id);
}

// Make components globally available
window.KSP = KSP;
</script>
