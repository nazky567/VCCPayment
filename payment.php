<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$order_id) {
    header("Location: checkout.php");
    exit;
}

// Fetch transaction from database
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE order_id = ?");
$stmt->execute([$order_id]);
$trx = $stmt->fetch();

if (!$trx) {
    $_SESSION['error_message'] = "Transaksi tidak ditemukan.";
    header("Location: checkout.php");
    exit;
}

// Validate ownership: only owner or admin can access the payment page
if ($_SESSION['role'] !== 'admin' && (int)$trx['user_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;background:#fce8e6;color:#a82c2c;border-radius:8px;border:1px solid #f5c2c2;'>
            <h2 style='margin-top:0;'>403 Forbidden - Akses Ditolak</h2>
            <p>Anda hanya diperbolehkan mengakses halaman pembayaran untuk transaksi milik Anda sendiri.</p>
            <hr style='border-color:#f5c2c2;'>
            <a href='dashboard.php' style='color:#a82c2c;text-decoration:none;font-weight:bold;'>&larr; Kembali ke Dashboard</a>
         </div>");
}

$is_mock = (strpos($trx['snap_token'], 'MOCK-SNAP-TOKEN-') === 0);
$client_key = getenv('MIDTRANS_CLIENT_KEY') ?: 'SB-Mid-client-placeholder';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selesaikan Pembayaran - CloudPay AI</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <?php if (!$is_mock): ?>
        <!-- Midtrans SNAP JS -->
        <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?php echo htmlspecialchars($client_key); ?>"></script>
    <?php endif; ?>
</head>
<body>

    <!-- Header / Navbar -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-custom">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-credit-card text-primary me-2"></i>Status Pembayaran</h4>
                        <span class="badge-status status-pending"><i class="bi bi-hourglass-split"></i> <?php echo htmlspecialchars($trx['transaction_status']); ?></span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-secondary small">Order ID</h6>
                                <p class="fw-bold fs-5 text-primary"><?php echo htmlspecialchars($trx['order_id']); ?></p>

                                <h6 class="text-secondary small">Nama Pelanggan</h6>
                                <p class="fw-semibold"><?php echo htmlspecialchars($trx['customer_name']); ?></p>

                                <h6 class="text-secondary small">Layanan / Produk</h6>
                                <p class="fw-semibold"><?php echo htmlspecialchars($trx['product_name']); ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <h6 class="text-secondary small">Total Tagihan</h6>
                                <p class="display-6 fw-bold text-white">Rp <?php echo number_format($trx['amount'], 0, ',', '.'); ?></p>

                                <h6 class="text-secondary small">Status Database</h6>
                                <p><i class="bi bi-database-fill-check text-success"></i> Tersimpan (Pending)</p>
                            </div>
                        </div>

                        <hr class="border-secondary my-4">

                        <?php if ($is_mock): ?>
                            <!-- MOCK CONTROLS (Self-contained simulation) -->
                            <div class="p-4 rounded border border-warning" style="background-color: rgba(245, 158, 11, 0.05);">
                                <h5 class="text-warning fw-bold mb-2"><i class="bi bi-info-circle-fill me-2"></i>Simulasi Sandbox Offline</h5>
                                <p class="text-secondary small mb-4">
                                    Midtrans API tidak aktif karena Server Key belum dikonfigurasi di file <code>.env</code> atau vendor/autoload belum terinstal. Gunakan panel simulasi di bawah untuk menguji alur sistem (database update, history events, email logs, dan redirection).
                                </p>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <button class="btn btn-success px-4 py-2 text-white" onclick="simulateCallback('settlement')">
                                        <i class="bi bi-check-circle-fill me-1"></i> Simulasikan Sukses (Settlement)
                                    </button>
                                    <button class="btn btn-warning px-4 py-2 text-dark" onclick="simulateCallback('pending')">
                                        <i class="bi bi-clock-fill me-1"></i> Simulasikan Pending
                                    </button>
                                    <button class="btn btn-danger px-4 py-2 text-white" onclick="simulateCallback('expire')">
                                        <i class="bi bi-x-circle-fill me-1"></i> Simulasikan Gagal/Expired
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Real Midtrans Snap Payment Trigger -->
                            <div class="text-center py-4">
                                <p class="text-secondary mb-4">Lakukan pembayaran Sandbox menggunakan Snap Popup Midtrans.</p>
                                <button id="pay-button" class="btn btn-gradient-primary btn-lg px-5 py-3">
                                    <i class="bi bi-wallet2 me-2"></i>Buka Snap Payment
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4 text-center">
                            <a href="checkout.php" class="btn btn-outline-custom"><i class="bi bi-arrow-left"></i> Kembali ke Checkout</a>
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
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (!$is_mock): ?>
    <script type="text/javascript">
        const payButton = document.getElementById('pay-button');
        const snapToken = '<?php echo $trx['snap_token']; ?>';
        const orderId = '<?php echo $trx['order_id']; ?>';

        payButton.addEventListener('click', function () {
            snap.pay(snapToken, {
                onSuccess: function (result) {
                    // Log event
                    logPaymentEvent(orderId, 'Snap Opened & Completed', result);
                    window.location.href = 'success.php?order_id=' + encodeURIComponent(orderId);
                },
                onPending: function (result) {
                    logPaymentEvent(orderId, 'Snap Opened & Pending', result);
                    window.location.href = 'pending.php?order_id=' + encodeURIComponent(orderId);
                },
                onError: function (result) {
                    logPaymentEvent(orderId, 'Snap Opened & Error', result);
                    window.location.href = 'failed.php?order_id=' + encodeURIComponent(orderId);
                },
                onClose: function () {
                    alert('Anda menutup popup pembayaran sebelum menyelesaikan transaksi.');
                }
            });
        });

        function logPaymentEvent(orderId, eventName, eventData) {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('event_name', eventName);
            formData.append('event_data', JSON.stringify(eventData));
            
            fetch('payment_logger_ajax.php', {
                method: 'POST',
                body: formData
            });
        }
    </script>
    <?php else: ?>
    <script type="text/javascript">
        // Client-side simulation of Callback notification
        function simulateCallback(status) {
            const orderId = '<?php echo $trx['order_id']; ?>';
            const amount = '<?php echo $trx['amount']; ?>';
            
            // Build mock notification payload
            const payload = {
                transaction_time: new Date().toISOString().slice(0, 19).replace('T', ' '),
                transaction_status: status,
                status_code: status === 'settlement' ? '200' : (status === 'pending' ? '201' : '202'),
                signature_key: 'MOCK_SIGNATURE_KEY_VALIDATED', // bypassing validation inside notification.php for mock
                gross_amount: amount,
                order_id: orderId,
                payment_type: 'bank_transfer',
                mock_simulation: true // flag to indicate local simulation
            };

            // Loader style transitions
            const cardBody = document.querySelector('.card-body');
            cardBody.style.opacity = '0.5';
            
            // Send request to notification callback handler
            fetch('notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.text())
            .then(data => {
                // Redirect according to simulated status
                if (status === 'settlement') {
                    window.location.href = 'success.php?order_id=' + encodeURIComponent(orderId);
                } else if (status === 'pending') {
                    window.location.href = 'pending.php?order_id=' + encodeURIComponent(orderId);
                } else {
                    window.location.href = 'failed.php?order_id=' + encodeURIComponent(orderId);
                }
            })
            .catch(error => {
                alert('Gagal mensimulasikan callback: ' + error);
                cardBody.style.opacity = '1';
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
