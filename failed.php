<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';
require_once __DIR__ . '/config/payment_helpers.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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
                    $stmtAudit->execute(['System_Redirect_Fallback', 'Update Transaction Status', "Transaction $order_id updated to $db_status via failed redirect check.", $_SERVER['REMOTE_ADDR']]);
                    
                    // If status is settlement, redirect to success page
                    if ($db_status == 'settlement') {
                        header("Location: success.php?order_id=" . urlencode($order_id));
                        exit;
                    } else {
                        // Reload transaction data
                        $stmt->execute([$order_id]);
                        $trx = $stmt->fetch();
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Midtrans Transaction Status check failure: " . $e->getMessage());
        }
    }
}

// Route based on actual transaction status to prevent displaying failed UI for success/pending transactions
$current_status = $trx['transaction_status'];
if ($current_status === 'settlement' || $current_status === 'capture' || $current_status === 'success') {
    header("Location: success.php?order_id=" . urlencode($order_id));
    exit;
} elseif ($current_status === 'pending') {
    header("Location: pending.php?order_id=" . urlencode($order_id));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Gagal - CloudPay Sandbox</title>
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
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-custom text-center">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <span class="d-inline-flex align-items-center justify-content-center bg-danger text-white rounded-circle" style="width: 80px; height: 80px; font-size: 2.5rem; box-shadow: 0 0 20px rgba(239, 68, 68, 0.4);">
                                <i class="bi bi-x-lg"></i>
                            </span>
                        </div>
                        <h2 class="text-danger fw-bold mb-2">Pembayaran Gagal!</h2>
                        <p class="text-secondary small mb-4">Transaksi Anda dibatalkan, ditolak, atau kedaluwarsa di sistem Midtrans Sandbox.</p>

                        <div class="p-3 rounded mb-4" style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color);">
                            <div class="row g-2 text-start">
                                <div class="col-6 text-secondary small">Order ID</div>
                                <div class="col-6 text-end fw-semibold text-primary"><?php echo htmlspecialchars($trx['order_id']); ?></div>

                                <div class="col-6 text-secondary small">Layanan</div>
                                <div class="col-6 text-end fw-semibold text-white"><?php echo htmlspecialchars($trx['product_name']); ?></div>

                                <div class="col-6 text-secondary small">Total Nominal</div>
                                <div class="col-6 text-end fw-bold text-danger">Rp <?php echo number_format($trx['amount'], 0, ',', '.'); ?></div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="btn btn-gradient-secondary py-3">
                                <i class="bi bi-arrow-repeat me-2"></i> Coba Pembayaran Baru
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
