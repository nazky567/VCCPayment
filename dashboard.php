<?php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

// Redirect admin to their own dashboard
if ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    // 1. Calculate Personal Metrics
    // Total Transactions
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
    $stmtTotal->execute([$user_id]);
    $total_trx = $stmtTotal->fetchColumn();

    // Successful Transactions
    $stmtSuccess = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND transaction_status IN ('settlement', 'capture', 'success')");
    $stmtSuccess->execute([$user_id]);
    $success_trx = $stmtSuccess->fetchColumn();

    // Pending Transactions
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND transaction_status = 'pending'");
    $stmtPending->execute([$user_id]);
    $pending_trx = $stmtPending->fetchColumn();

    // Total Spent (Simulated)
    $stmtSpent = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND transaction_status IN ('settlement', 'capture', 'success')");
    $stmtSpent->execute([$user_id]);
    $total_spent = $stmtSpent->fetchColumn() ?: 0;

    // 2. Fetch User's Transactions (Latest 50)
    $stmtTrx = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmtTrx->execute([$user_id]);
    $transactions = $stmtTrx->fetchAll();

} catch (\Exception $e) {
    error_log("User dashboard query failed: " . $e->getMessage());
    die("Gagal memuat data dashboard. Silakan hubungi administrator.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - CloudPay Sandbox</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Header / Navbar -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid px-4 my-4">
        <!-- Welcome banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom p-4" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(139, 92, 246, 0.08) 100%); border-color: rgba(99, 102, 241, 0.2);">
                    <div class="d-md-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1 text-white">Selamat Datang, <?php echo htmlspecialchars($username); ?>!</h2>
                            <p class="text-secondary mb-0">Kelola simulasi pembelian langganan AI Premium (AI Pro) Anda di panel sandbox ini.</p>
                        </div>
                        <div class="mt-3 mt-md-0">
                            <a href="checkout.php" class="btn btn-gradient-primary btn-lg"><i class="bi bi-cart-plus me-1"></i> Beli Layanan Baru</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card card-custom p-4 metric-card">
                    <span class="text-secondary small uppercase font-semibold">Total Transaksi Anda</span>
                    <div class="metric-value text-white"><?php echo number_format($total_trx); ?></div>
                    <small class="text-secondary">Seluruh request pembayaran Anda</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-4 metric-card">
                    <span class="text-secondary small uppercase font-semibold text-success">Transaksi Berhasil</span>
                    <div class="metric-value text-success"><?php echo number_format($success_trx); ?></div>
                    <small class="text-secondary">Sudah terbayar & terkonfirmasi</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-4 metric-card">
                    <span class="text-secondary small uppercase font-semibold text-warning">Transaksi Tertunda</span>
                    <div class="metric-value text-warning"><?php echo number_format($pending_trx); ?></div>
                    <small class="text-secondary">Menunggu pembayaran Midtrans</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-4 metric-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(17, 24, 39, 0.7) 100%);">
                    <span class="text-secondary small uppercase font-semibold text-success">Total Dana Terbayar</span>
                    <div class="metric-value text-success" style="font-size: 1.8rem; margin-top: 5px;">Rp <?php echo number_format($total_spent, 0, ',', '.'); ?></div>
                    <small class="text-secondary">Simulasi dana berhasil terproses</small>
                </div>
            </div>
        </div>

        <!-- Transaction History Table -->
        <div class="card card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Transaksi Anda</h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Order ID</th>
                                <th>Layanan / Produk</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transactions) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">Belum ada transaksi. Silakan buat transaksi baru!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $tx): ?>
                                    <tr>
                                        <td class="text-secondary" style="font-size: 0.85rem;"><?php echo date('d M Y H:i', strtotime($tx['created_at'])); ?> WIB</td>
                                        <td class="text-primary fw-semibold"><?php echo htmlspecialchars($tx['order_id']); ?></td>
                                        <td class="text-white"><?php echo htmlspecialchars($tx['product_name']); ?></td>
                                        <td class="fw-semibold">Rp <?php echo number_format($tx['amount'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge-status status-<?php echo htmlspecialchars($tx['transaction_status']); ?>">
                                                <?php echo htmlspecialchars($tx['transaction_status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($tx['transaction_status'] === 'pending'): ?>
                                                <!-- Action to open Snap interface -->
                                                <a href="payment.php?order_id=<?php echo urlencode($tx['order_id']); ?>" class="btn btn-gradient-primary btn-sm px-3 py-1">
                                                    <i class="bi bi-wallet2 me-1"></i> Bayar
                                                </a>
                                            <?php elseif (in_array($tx['transaction_status'], ['settlement', 'capture', 'success'])): ?>
                                                <!-- Download Invoice -->
                                                <a href="admin/export.php?action=pdf&order_id=<?php echo urlencode($tx['order_id']); ?>" class="btn btn-outline-custom btn-sm px-3 py-1">
                                                    <i class="bi bi-file-earmark-pdf me-1"></i> Invoice PDF
                                                </a>
                                            <?php else: ?>
                                                <span class="text-secondary small">Tidak ada aksi</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p class="mb-1"><strong>CloudPay Sandbox Simulator</strong> &copy; 2026. Dashboard Pembayaran Virtualisasi Cloud.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
