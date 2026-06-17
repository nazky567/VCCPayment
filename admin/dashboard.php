<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Access control: Ensure user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];

try {
    // 1. Calculate General Metrics
    // Total Transactions
    $total_trx = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    
    // Total Success
    $success_trx = $pdo->query("SELECT COUNT(*) FROM transactions WHERE transaction_status IN ('settlement', 'capture', 'success')")->fetchColumn();
    
    // Total Pending
    $pending_trx = $pdo->query("SELECT COUNT(*) FROM transactions WHERE transaction_status = 'pending'")->fetchColumn();
    
    // Total Failed/Cancelled
    $failed_trx = $pdo->query("SELECT COUNT(*) FROM transactions WHERE transaction_status IN ('expire', 'deny', 'cancel', 'failed')")->fetchColumn();
    
    // Revenue metrics
    $revenue_today = $pdo->query("SELECT SUM(amount) FROM transactions WHERE transaction_status IN ('settlement', 'capture', 'success') AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
    $revenue_month = $pdo->query("SELECT SUM(amount) FROM transactions WHERE transaction_status IN ('settlement', 'capture', 'success') AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn() ?: 0;

    // 2. Fetch Chart Data: Status Distribution (Pie Chart)
    $status_data_query = $pdo->query("SELECT transaction_status, COUNT(*) as count FROM transactions GROUP BY transaction_status");
    $statuses_map = ['success' => 0, 'pending' => 0, 'failed' => 0];
    
    while ($row = $status_data_query->fetch()) {
        $st = $row['transaction_status'];
        if ($st == 'settlement' || $st == 'capture' || $st == 'success') {
            $statuses_map['success'] += $row['count'];
        } else if ($st == 'pending') {
            $statuses_map['pending'] += $row['count'];
        } else {
            $statuses_map['failed'] += $row['count'];
        }
    }

    // 3. Fetch Chart Data: Daily Volume Last 7 Days (Line Chart)
    $daily_data_query = $pdo->query("
        SELECT DATE(created_at) as date_only, COUNT(*) as count 
        FROM transactions 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at) 
        ORDER BY DATE(created_at) ASC
    ");
    
    $daily_labels = [];
    $daily_counts = [];
    while ($row = $daily_data_query->fetch()) {
        $daily_labels[] = date('d M', strtotime($row['date_only']));
        $daily_counts[] = (int)$row['count'];
    }

    // 4. Latest 5 Transactions
    $latest_trx = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // 5. Latest 5 Audit Logs
    $latest_audit = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();

} catch (\Exception $e) {
    error_log("Dashboard query exception: " . $e->getMessage());
    die("Gagal memuat data dashboard. Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - CloudPay Sandbox</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-cloud-fill"></i>
                <span class="brand-title">CloudPay Sandbox Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php"><i class="bi bi-wallet2"></i> Transaksi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="callback-monitor.php"><i class="bi bi-terminal-dash"></i> Live Monitor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="system-info.php"><i class="bi bi-cpu"></i> Info Server</a>
                    </li>
                </ul>
                <div class="navbar-nav ms-auto align-items-center">
                    <span class="text-secondary me-3" style="font-size:0.9rem;">
                        Halo, <strong class="text-white"><?php echo htmlspecialchars($username); ?></strong> 
                        <span class="badge bg-primary text-uppercase" style="font-size: 0.7rem;"><?php echo htmlspecialchars($role); ?></span>
                    </span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm py-1.5 px-3"><i class="bi bi-box-arrow-right"></i> Keluar</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid px-4 my-4">
        
        <!-- Welcome banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-custom p-4" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%);">
                    <div class="d-md-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Monitoring Virtualisasi Cloud Computing</h2>
                            <p class="text-secondary mb-0">Selamat datang kembali. Berikut ringkasan transaksi & audit log sandbox payment gateway.</p>
                        </div>
                        <div class="mt-3 mt-md-0 d-flex gap-2">
                            <a href="../seed.php" class="btn btn-outline-custom text-warning border-warning"><i class="bi bi-database-add"></i> Seed Dummy Data</a>
                            <a href="../checkout.php" target="_blank" class="btn btn-gradient-primary"><i class="bi bi-cart3"></i> Buka Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card card-custom p-4 metric-card">
                    <span class="text-secondary small uppercase font-semibold">Total Transaksi</span>
                    <div class="metric-value text-white"><?php echo number_format($total_trx); ?></div>
                    <small class="text-secondary">Seluruh request Snap API</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-4 metric-card">
                    <span class="text-secondary small uppercase font-semibold text-success">Sukses (Settlement)</span>
                    <div class="metric-value text-success"><?php echo number_format($success_trx); ?></div>
                    <small class="text-secondary">Simulasi pembayaran berhasil</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-4 metric-card">
                    <span class="text-secondary small uppercase font-semibold text-warning">Pending</span>
                    <div class="metric-value text-warning"><?php echo number_format($pending_trx); ?></div>
                    <small class="text-secondary">Menunggu instruksi Snap</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-4 metric-card">
                    <span class="text-secondary small uppercase font-semibold text-danger">Gagal / Kadaluwarsa</span>
                    <div class="metric-value text-danger"><?php echo number_format($failed_trx); ?></div>
                    <small class="text-secondary">Expired, Deny, Cancel</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-custom p-4 metric-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(17, 24, 39, 0.7) 100%);">
                    <span class="text-secondary small uppercase font-semibold">Pendapatan Hari Ini (Simulasi)</span>
                    <div class="metric-value text-success">Rp <?php echo number_format($revenue_today, 0, ',', '.'); ?></div>
                    <small class="text-secondary">Jumlah dana terproses hari ini</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-custom p-4 metric-card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(17, 24, 39, 0.7) 100%);">
                    <span class="text-secondary small uppercase font-semibold">Pendapatan Bulan Ini (Simulasi)</span>
                    <div class="metric-value text-primary">Rp <?php echo number_format($revenue_month, 0, ',', '.'); ?></div>
                    <small class="text-secondary">Jumlah akumulasi bulan berjalan</small>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="row g-4 mb-4">
            <!-- Line Chart -->
            <div class="col-lg-8">
                <div class="card card-custom h-100">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Frekuensi Transaksi Harian (7 Hari Terakhir)</h5>
                    </div>
                    <div class="card-body p-4 d-flex align-items-center justify-content-center">
                        <div style="width: 100%; height: 280px; position: relative;">
                            <canvas id="dailyVolumeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Pie Chart -->
            <div class="col-lg-4">
                <div class="card card-custom h-100">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-pie-chart text-secondary me-2"></i>Status Distribusi</h5>
                    </div>
                    <div class="card-body p-4 d-flex align-items-center justify-content-center">
                        <div style="width: 100%; height: 280px; position: relative;">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lower Grid: Tables -->
        <div class="row g-4">
            <!-- Latest Transactions -->
            <div class="col-xl-7">
                <div class="card card-custom h-100">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-table text-primary me-2"></i>5 Transaksi Terbaru</h5>
                        <a href="transactions.php" class="btn btn-outline-custom btn-sm">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-custom align-middle">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Pelanggan</th>
                                        <th>Layanan</th>
                                        <th>Nominal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($latest_trx) == 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-secondary py-4">Belum ada data transaksi.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($latest_trx as $tx): ?>
                                            <tr>
                                                <td class="text-primary fw-semibold"><?php echo htmlspecialchars($tx['order_id']); ?></td>
                                                <td>
                                                    <div class="fw-semibold text-white"><?php echo htmlspecialchars($tx['customer_name']); ?></div>
                                                    <small class="text-secondary" style="font-size:0.75rem;"><?php echo htmlspecialchars($tx['email']); ?></small>
                                                </td>
                                                <td class="text-secondary" style="font-size:0.85rem;"><?php echo htmlspecialchars($tx['product_name']); ?></td>
                                                <td class="fw-semibold">Rp <?php echo number_format($tx['amount'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <span class="badge-status status-<?php echo htmlspecialchars($tx['transaction_status']); ?>">
                                                        <?php echo htmlspecialchars($tx['transaction_status']); ?>
                                                    </span>
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

            <!-- Audit Trail -->
            <div class="col-xl-5">
                <div class="card card-custom h-100">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-shield-check text-secondary me-2"></i>Audit Trail Log Aktivitas</h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-custom align-middle" style="font-size:0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aksi</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($latest_audit) == 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-secondary py-4">Belum ada audit log.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($latest_audit as $log): ?>
                                            <tr>
                                                <td class="text-secondary" style="font-size: 0.75rem;"><?php echo date('d/m H:i', strtotime($log['created_at'])); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['username']); ?></span></td>
                                                <td class="text-white"><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td class="text-secondary"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer>
        <div class="container-fluid px-4 text-center">
            <p class="mb-1"><strong>CloudPay Sandbox Simulator</strong> &copy; 2026. Dashboard Monitoring Cloud Virtualization.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart rendering Javascript -->
    <script>
        // Data derived from PHP arrays
        const dailyLabels = <?php echo json_encode($daily_labels); ?>;
        const dailyCounts = <?php echo json_encode($daily_counts); ?>;

        const successCount = <?php echo $statuses_map['success']; ?>;
        const pendingCount = <?php echo $statuses_map['pending']; ?>;
        const failedCount = <?php echo $statuses_map['failed']; ?>;

        // Line Chart (Daily Volume)
        const lineCtx = document.getElementById('dailyVolumeChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: dailyLabels.length ? dailyLabels : ['Hari Ini'],
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: dailyCounts.length ? dailyCounts : [0],
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#8B5CF6',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#9CA3AF' }
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#9CA3AF', stepSize: 1 },
                        beginAtZero: true
                    }
                }
            }
        });

        // Pie Chart (Status Distribution)
        const pieCtx = document.getElementById('statusDistributionChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Sukses', 'Pending', 'Gagal/Expired'],
                datasets: [{
                    data: [successCount, pendingCount, failedCount],
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                    borderColor: 'rgba(15, 23, 42, 0.8)',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#9CA3AF', font: { family: 'Inter', size: 11 } }
                    }
                },
                cutout: '65%'
            }
        });
    </script>
</body>
</html>
