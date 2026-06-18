<?php
require_once 'includes/auth_check.php';

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
    <title>AI Subscription Hub - CloudPay AI</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body>

    <!-- Header / Navbar -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <span class="badge bg-primary px-3 py-2 rounded-pill mb-3">LANGGANAN AI PREMIUM</span>
                    <h1 class="display-5 fw-bold">AI Subscription Hub</h1>
                    <p class="text-secondary col-md-8 mx-auto">
                        Pilih paket asisten AI premium Anda. Transaksi diproses secara aman menggunakan simulator Sandbox.
                    </p>
                </div>

                <!-- AI Pricing Cards Section -->
                <div class="row g-3 mb-5">
                    <div class="col-md-3 col-sm-6">
                        <div class="card card-custom pricing-card h-100" data-plan="chatgpt_plus" data-price="320000" data-name="ChatGPT Plus 1 Bulan" onclick="selectPlan(this)">
                            <div class="pricing-card-badge bg-chatgpt"><i class="bi bi-openai"></i> ChatGPT Plus</div>
                            <div class="pricing-card-body">
                                <i class="bi bi-chat-right-dots-fill icon-ai text-chatgpt"></i>
                                <h5 class="plan-title">Plus</h5>
                                <div class="plan-price">Rp 320.000<span class="plan-period">/bln</span></div>
                                <ul class="plan-features text-secondary small">
                                    <li><i class="bi bi-check2"></i> GPT-4o & GPT-4</li>
                                    <li><i class="bi bi-check2"></i> Pembuatan Gambar DALL-E</li>
                                    <li><i class="bi bi-check2"></i> Analisis Data Lanjutan</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card card-custom pricing-card h-100" data-plan="claude_pro" data-price="320000" data-name="Claude Pro 1 Bulan" onclick="selectPlan(this)">
                            <div class="pricing-card-badge bg-claude"><i class="bi bi-robot"></i> Claude Pro</div>
                            <div class="pricing-card-body">
                                <i class="bi bi-cpu-fill icon-ai text-claude"></i>
                                <h5 class="plan-title">Pro</h5>
                                <div class="plan-price">Rp 320.000<span class="plan-period">/bln</span></div>
                                <ul class="plan-features text-secondary small">
                                    <li><i class="bi bi-check2"></i> Model Claude 3.5 Sonnet</li>
                                    <li><i class="bi bi-check2"></i> Limit Penggunaan 5x Lebih Banyak</li>
                                    <li><i class="bi bi-check2"></i> Akses Awal Fitur Baru</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card card-custom pricing-card h-100" data-plan="gemini_adv" data-price="300000" data-name="Gemini Advanced 1 Bulan" onclick="selectPlan(this)">
                            <div class="pricing-card-badge bg-gemini"><i class="bi bi-stars"></i> Gemini Advanced</div>
                            <div class="pricing-card-body">
                                <i class="bi bi-stars icon-ai text-gemini"></i>
                                <h5 class="plan-title">Advanced</h5>
                                <div class="plan-price">Rp 300.000<span class="plan-period">/bln</span></div>
                                <ul class="plan-features text-secondary small">
                                    <li><i class="bi bi-check2"></i> Akses Google 1.5 Pro</li>
                                    <li><i class="bi bi-check2"></i> Penyimpanan Google One 2TB</li>
                                    <li><i class="bi bi-check2"></i> Integrasi ke Gmail & Docs</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card card-custom pricing-card h-100" data-plan="perplexity_pro" data-price="300000" data-name="Perplexity Pro 1 Bulan" onclick="selectPlan(this)">
                            <div class="pricing-card-badge bg-perplexity"><i class="bi bi-search"></i> Perplexity Pro</div>
                            <div class="pricing-card-body">
                                <i class="bi bi-search-heart icon-ai text-perplexity"></i>
                                <h5 class="plan-title">Pro</h5>
                                <div class="plan-price">Rp 300.000<span class="plan-period">/bln</span></div>
                                <ul class="plan-features text-secondary small">
                                    <li><i class="bi bi-check2"></i> Pilihan Model Copilot Lanjutan</li>
                                    <li><i class="bi bi-check2"></i> Unggah & Analisis File Unlimited</li>
                                    <li><i class="bi bi-check2"></i> Kredit API bulanan gratis</li>
                                </ul>
                            </div>
                        </div>
                    </div>
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
                                                <option value="chatgpt_plus" data-price="320000" selected>ChatGPT Plus 1 Bulan</option>
                                                <option value="claude_pro" data-price="320000">Claude Pro 1 Bulan</option>
                                                <option value="gemini_adv" data-price="300000">Gemini Advanced 1 Bulan</option>
                                                <option value="perplexity_pro" data-price="300000">Perplexity Pro 1 Bulan</option>
                                                <option value="bundle" data-price="600000">ChatGPT Plus + Claude Pro (Bundle)</option>
                                                <option value="custom">-- Input Custom --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label for="amount" class="form-label form-label-custom">Harga Produk (Rp)</label>
                                            <input type="number" class="form-control form-control-custom" id="amount" name="amount" value="320000" readonly required>
                                        </div>
                                        <div class="col-12" id="custom_product_field" style="display: none;">
                                            <label for="product_name_custom" class="form-label form-label-custom">Nama Produk Custom</label>
                                            <input type="text" class="form-control form-control-custom" id="product_name_custom" name="product_name_custom" placeholder="Nama Layanan AI Custom">
                                        </div>
                                        <input type="hidden" id="product_name" name="product_name" value="ChatGPT Plus 1 Bulan">

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
                                        <h5 id="summary_product_name" class="fw-semibold">ChatGPT Plus 1 Bulan</h5>
                                    </div>

                                    <hr class="border-secondary my-3">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-secondary">Subtotal</span>
                                        <span class="fw-semibold text-white" id="summary_subtotal">Rp 320.000</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-secondary">PPN (11%)</span>
                                        <span class="text-success fw-semibold">Rp 0 (Sandbox Fee Waived)</span>
                                    </div>
                                    <hr class="border-secondary my-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Total Pembayaran</span>
                                        <span class="fs-4 fw-bold text-primary" id="summary_total">Rp 320.000</span>
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
            <p class="mb-1"><strong>CloudPay AI Simulator</strong> &copy; 2026. Tugas Akhir Virtualisasi Cloud Computing.</p>
            <p class="text-secondary mb-0" style="font-size: 0.8rem;">Dikembangkan untuk simulasi infrastruktur cloud hosting cPanel.</p>
        </div>
    </footer>

    <!-- Toast Notification Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
        <div id="validationToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" style="backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <span id="toastMessage">Semua data form harus diisi dengan benar.</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

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
            
            // Sync pricing cards selection styling
            document.querySelectorAll('.pricing-card').forEach(c => {
                c.classList.remove('active');
            });
            const activeCard = document.querySelector(`.pricing-card[data-plan="${select.value}"]`);
            if (activeCard) {
                activeCard.classList.add('active');
            }
            
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
