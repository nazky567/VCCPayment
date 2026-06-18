<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';
require_once __DIR__ . '/config/payment_helpers.php';

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$order_id) {
    header("Location: checkout.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE order_id = ?");
$stmt->execute([$order_id]);
$trx = $stmt->fetch();

if (!$trx) {
    $_SESSION['error_message'] = "Transaksi tidak ditemukan.";
    header("Location: checkout.php");
    exit;
}

// Validate ownership: only owner or admin can view this page
if ($_SESSION['role'] !== 'admin' && (int)$trx['user_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;background:#fce8e6;color:#a82c2c;border-radius:8px;border:1px solid #f5c2c2;'>
            <h2 style='margin-top:0;'>403 Forbidden - Akses Ditolak</h2>
            <p>Anda hanya diperbolehkan mengakses transaksi milik Anda sendiri.</p>
            <hr style='border-color:#f5c2c2;'>
            <a href='dashboard.php' style='color:#a82c2c;text-decoration:none;font-weight:bold;'>&larr; Kembali ke Dashboard</a>
         </div>");
}

// Fallback: If status is still pending, check with Midtrans API to update local DB
if ($trx['transaction_status'] === 'pending' && strpos($trx['snap_token'], 'MOCK-') !== 0) {
    $midConfig = require __DIR__ . '/config/midtrans.php';
    if ($midConfig['isConfigured'] && class_exists('\Midtrans\Transaction')) {
        try {
            $status_response = \Midtrans\Transaction::status($order_id);
            if ($status_response) {
                $status_response = (array)$status_response;
                $transaction_status = isset($status_response['transaction_status']) ? $status_response['transaction_status'] : '';
                $payment_type = isset($status_response['payment_type']) ? $status_response['payment_type'] : '';
                
                if ($transaction_status !== 'pending') {
                    // Update database
                    $db_status = $transaction_status;
                    if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
                        $db_status = 'settlement';
                    }
                    
                    $stmtUpdate = $pdo->prepare("UPDATE transactions SET transaction_status = ?, payment_type = ?, transaction_time = NOW() WHERE order_id = ?");
                    $stmtUpdate->execute([$db_status, $payment_type, $order_id]);
                    
                    // Log Timeline Event
                    $stmtEvent = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data) VALUES (?, ?, ?)");
                    $stmtEvent->execute([$order_id, "Status Updated via Redirect Fallback: " . strtoupper($db_status), json_encode($status_response)]);
                    
                    // Generate Invoice PDF if successful
                    $pdfPath = '';
                    if ($db_status == 'settlement') {
                        $pdfPath = generateInvoicePDF($trx, $db_status, $payment_type);
                        if ($pdfPath) {
                            $stmtUpdatePdf = $pdo->prepare("UPDATE transactions SET pdf_invoice_path = ? WHERE order_id = ?");
                            $stmtUpdatePdf->execute([$pdfPath, $order_id]);
                        }
                    }
                    
                    // Send Email notification to customer
                    sendNotificationEmail($trx, $db_status, $pdfPath);
                    
                    // Audit Log
                    $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address) VALUES (?, ?, ?, ?)");
                    $stmtAudit->execute(['System_Redirect_Fallback', 'Update Transaction Status', "Transaction $order_id updated to $db_status via pending redirect check.", $_SERVER['REMOTE_ADDR']]);
                    
                    // If status is no longer pending, redirect to success or failed page
                    if ($db_status == 'settlement') {
                        header("Location: success.php?order_id=" . urlencode($order_id));
                        exit;
                    } else {
                        header("Location: failed.php?order_id=" . urlencode($order_id));
                        exit;
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Midtrans Transaction Status check failure: " . $e->getMessage());
        }
    }
}

// Route based on actual transaction status to prevent displaying pending UI for success/failed transactions
$current_status = $trx['transaction_status'];
if ($current_status === 'settlement' || $current_status === 'capture' || $current_status === 'success') {
    header("Location: success.php?order_id=" . urlencode($order_id));
    exit;
} elseif (in_array($current_status, ['expire', 'deny', 'cancel', 'failed'])) {
    header("Location: failed.php?order_id=" . urlencode($order_id));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Pending - CloudPay AI</title>
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
            <div class="col-md-6">
                <div class="card card-custom text-center">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center bg-warning text-dark rounded-circle" style="width: 80px; height: 80px; font-size: 2.5rem; box-shadow: 0 0 20px rgba(245, 158, 11, 0.4);">
                                <i class="bi bi-hourglass-split"></i>
                            </span>
                        </div>
                        <h2 class="text-warning fw-bold mb-2">Pembayaran Pending!</h2>
                        <p class="text-secondary small mb-4">Transaksi Anda telah dibuat dan sedang menunggu pembayaran diselesaikan via channel Midtrans Sandbox.</p>

                        <div class="p-3 rounded mb-4" style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color);">
                            <div class="row g-2 text-start">
                                <div class="col-6 text-secondary small">Order ID</div>
                                <div class="col-6 text-end fw-semibold text-primary"><?php echo htmlspecialchars($trx['order_id']); ?></div>

                                <div class="col-6 text-secondary small">Layanan</div>
                                <div class="col-6 text-end fw-semibold text-white"><?php echo htmlspecialchars($trx['product_name']); ?></div>

                                <div class="col-6 text-secondary small">Total Nominal</div>
                                <div class="col-6 text-end fw-bold text-warning">Rp <?php echo number_format($trx['amount'], 0, ',', '.'); ?></div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2" role="alert" style="background-color: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); color: #60A5FA; font-size: 0.85rem;">
                            <i class="bi bi-info-circle-fill me-1"></i> Silakan selesaikan pembayaran Anda di simulasi Midtrans Sandbox. Status akan berubah otomatis.
                        </div>

                        <div class="d-grid gap-2">
                            <a href="payment.php?order_id=<?php echo urlencode($trx['order_id']); ?>" class="btn btn-gradient-primary py-3">
                                <i class="bi bi-credit-card-2-front-fill me-2"></i> Buka Pembayaran Kembali
                            </a>
                            <a href="index.php" class="btn btn-outline-custom py-2">
                                <i class="bi bi-house-door me-1"></i> Kembali ke Menu Utama
                            </a>
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
</body>
</html>
