<?php
session_start();

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Pembayaran - CloudPay Sandbox</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cloud-fill"></i>
                <span class="brand-title">CloudPay Sandbox</span>
            </a>
            <button class="navbar-dark navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house-door"></i> Beranda</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php"><i class="bi bi-speedometer2"></i> Admin Dashboard</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="checkout.php"><i class="bi bi-cart3"></i> Checkout</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="health-check.php"><i class="bi bi-heart-pulse"></i> Health Status</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php"><i class="bi bi-shield-lock"></i> Admin Portal</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="admin/logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <span class="badge bg-primary px-3 py-2 rounded-pill mb-3">SIMULASI TRANSAKSI</span>
                    <h1 class="display-5 fw-bold">E-Commerce Checkout Form</h1>
                    <p class="text-secondary col-md-8 mx-auto">
                        Simulasikan proses pengisian data pelanggan dan pemicuan gerbang pembayaran digital menggunakan API Snap Sandbox.
                    </p>
                </div>

                <div class="row g-4">
                    <!-- Checkout Form -->
                    <div class="col-md-7">
                        <div class="card card-custom h-100">
                            <div class="card-header-custom">
                                <h4 class="mb-0"><i class="bi bi-wallet2 text-primary me-2"></i>Informasi Pelanggan & Pembayaran</h4>
                            </div>
                            <div class="card-body p-4">
                                <?php if (isset($_SESSION['error_message'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #EF4444;">
                                        <strong>Error!</strong> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <form id="checkoutForm" action="checkout_process.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="customer_name" class="form-label form-label-custom">Nama Lengkap</label>
                                            <input type="text" class="form-control form-control-custom" id="customer_name" name="customer_name" placeholder="Budi Santoso" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="customer_phone" class="form-label form-label-custom">Nomor HP / WhatsApp</label>
                                            <input type="tel" class="form-control form-control-custom" id="customer_phone" name="customer_phone" placeholder="081234567890" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="customer_email" class="form-label form-label-custom">Alamat Email</label>
                                            <input type="email" class="form-control form-control-custom" id="customer_email" name="customer_email" placeholder="budi.santoso@example.com" required>
                                        </div>
                                        <div class="col-md-7">
                                            <label for="product_select" class="form-label form-label-custom">Pilih Produk</label>
                                            <select class="form-select form-control-custom" id="product_select" name="product_select" onchange="updateProductDetails(this)">
                                                <option value="cloud_pro" data-price="250000" selected>Cloud VPS Hosting Pro</option>
                                                <option value="cloud_ent" data-price="500000">Enterprise Cloud Cluster</option>
                                                <option value="domain_ssl" data-price="125000">Domain .COM + Premium SSL</option>
                                                <option value="custom">-- Input Custom --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label for="amount" class="form-label form-label-custom">Harga Produk (Rp)</label>
                                            <input type="number" class="form-control form-control-custom" id="amount" name="amount" value="250000" readonly required>
                                        </div>
                                        <div class="col-12" id="custom_product_field" style="display: none;">
                                            <label for="product_name_custom" class="form-label form-label-custom">Nama Produk Custom</label>
                                            <input type="text" class="form-control form-control-custom" id="product_name_custom" name="product_name_custom" placeholder="Nama Layanan Cloud Custom">
                                        </div>
                                        <input type="hidden" id="product_name" name="product_name" value="Cloud VPS Hosting Pro">

                                        <div class="col-12 mt-4">
                                            <button type="submit" class="btn btn-gradient-primary w-100 py-3" id="btnSubmitPay">
                                                <i class="bi bi-credit-card-2-front-fill me-2"></i>Bayar Sekarang (Sandbox)
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Order Summary & Info -->
                    <div class="col-md-5">
                        <div class="card card-custom h-100">
                            <div class="card-header-custom">
                                <h4 class="mb-0"><i class="bi bi-info-circle text-secondary me-2"></i>Ringkasan Layanan</h4>
                            </div>
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center mb-4 p-3 rounded" style="background-color: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.15);">
                                        <i class="bi bi-shield-check text-primary fs-3 me-3"></i>
                                        <div>
                                            <h6 class="mb-0 fw-bold">Midtrans Sandbox Mode</h6>
                                            <small class="text-secondary">Metode pembayaran disimulasikan secara aman tanpa dana riil.</small>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-secondary small">Item terpilih</label>
                                        <h5 id="summary_product_name" class="fw-semibold">Cloud VPS Hosting Pro</h5>
                                    </div>

                                    <hr class="border-secondary my-3">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-secondary">Subtotal</span>
                                        <span class="fw-semibold text-white" id="summary_subtotal">Rp 250.000</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-secondary">PPN (11%)</span>
                                        <span class="text-success fw-semibold">Rp 0 (Sandbox Fee Waived)</span>
                                    </div>
                                    <hr class="border-secondary my-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Total Pembayaran</span>
                                        <span class="fs-4 fw-bold text-primary" id="summary_total">Rp 250.000</span>
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-top border-secondary text-center">
                                    <small class="text-secondary block">
                                        <i class="bi bi-lock-fill me-1"></i> Data dikirim menggunakan SSL / TLS Terenkripsi.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p class="mb-1"><strong>CloudPay Sandbox Simulator</strong> &copy; 2026. Tugas Akhir Virtualisasi Cloud Computing.</p>
            <p class="text-secondary mb-0" style="font-size: 0.8rem;">Dikembangkan untuk simulasi infrastruktur cloud hosting cPanel.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>

    <script>
        function updateProductDetails(select) {
            const selectedOption = select.options[select.selectedIndex];
            const amountInput = document.getElementById('amount');
            const customProductField = document.getElementById('custom_product_field');
            const productNameInput = document.getElementById('product_name');
            const summaryProductName = document.getElementById('summary_product_name');
            const summarySubtotal = document.getElementById('summary_subtotal');
            const summaryTotal = document.getElementById('summary_total');
            
            if (select.value === 'custom') {
                amountInput.readOnly = false;
                amountInput.value = '';
                customProductField.style.display = 'block';
                productNameInput.value = '';
                summaryProductName.textContent = 'Custom Product';
                summarySubtotal.textContent = 'Rp 0';
                summaryTotal.textContent = 'Rp 0';
                document.getElementById('product_name_custom').required = true;
                
                // Add listener to custom name and amount to update summary dynamically
                document.getElementById('product_name_custom').addEventListener('input', function() {
                    productNameInput.value = this.value;
                    summaryProductName.textContent = this.value || 'Custom Product';
                });
                
                amountInput.addEventListener('input', function() {
                    const price = parseFloat(this.value) || 0;
                    summarySubtotal.textContent = 'Rp ' + price.toLocaleString('id-ID');
                    summaryTotal.textContent = 'Rp ' + price.toLocaleString('id-ID');
                });
            } else {
                amountInput.readOnly = true;
                const price = parseFloat(selectedOption.getAttribute('data-price'));
                amountInput.value = price;
                customProductField.style.display = 'none';
                document.getElementById('product_name_custom').required = false;
                
                const productName = selectedOption.text;
                productNameInput.value = productName;
                summaryProductName.textContent = productName;
                summarySubtotal.textContent = 'Rp ' + price.toLocaleString('id-ID');
                summaryTotal.textContent = 'Rp ' + price.toLocaleString('id-ID');
            }
        }
    </script>
</body>
</html>
