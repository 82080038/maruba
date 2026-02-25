<?php include view_path('layout/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Buka Rekening Simpanan Baru</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= route_url('index.php/savings/store') ?>">
                        <?= csrf_field() ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="member_id">Pilih Anggota <span class="text-danger">*</span></label>
                                    <select class="form-control" name="member_id" id="member_id" required>
                                        <option value="">-- Pilih Anggota --</option>
                                        <?php foreach ($members as $member): ?>
                                            <option value="<?= $member['id'] ?>">
                                                <?= htmlspecialchars($member['name']) ?> (NIK: <?= htmlspecialchars($member['nik']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="product_id">Produk Simpanan <span class="text-danger">*</span></label>
                                    <select class="form-control" name="product_id" id="product_id" required>
                                        <option value="">-- Pilih Produk --</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= $product['id'] ?>" data-rate="<?= $product['interest_rate'] ?>" data-minimum="<?= $product['minimum_balance'] ?>">
                                                <?= htmlspecialchars($product['name']) ?>
                                                <?php if ($product['interest_rate'] > 0): ?>
                                                    (<?= $product['interest_rate'] ?>% p.a.)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="initial_deposit">Setoran Awal (Rp)</label>
                                    <input type="number" class="form-control" name="initial_deposit" id="initial_deposit" min="0" step="1000" value="0">
                                    <small class="form-text text-muted">Kosongkan jika tidak ada setoran awal</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Informasi Produk</label>
                                    <div id="product-info" class="alert alert-info" style="display: none;">
                                        <strong id="product-name"></strong><br>
                                        <span id="product-details"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Buat Rekening
                            </button>
                            <a href="<?= route_url('index.php/savings') ?>" class="btn btn-secondary ml-2">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('product_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const productInfo = document.getElementById('product-info');
    const productName = document.getElementById('product-name');
    const productDetails = document.getElementById('product-details');

    if (this.value) {
        const rate = selectedOption.getAttribute('data-rate');
        const minimum = selectedOption.getAttribute('data-minimum');

        productName.textContent = selectedOption.textContent.split(' (')[0];
        productDetails.textContent = `Bunga: ${rate}%, Saldo Minimum: Rp ${new Intl.NumberFormat('id-ID').format(minimum)}`;
        productInfo.style.display = 'block';
    } else {
        productInfo.style.display = 'none';
    }
});
</script>

<?php include view_path('layout/footer'); ?>
