<?php
define('IS_ADMIN_DIR', true);
require_once __DIR__ . '/../includes/auth_check.php';
enforceAdmin();
require_once __DIR__ . '/../config/database.php';

$role = $_SESSION['role'];
$username = $_SESSION['username'];

// CSRF validation for modifying actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. Delete Action (Restricted to Admin only)
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    if ($role !== 'admin') {
        $_SESSION['error_message'] = "Akses ditolak: Hanya administrator yang dapat menghapus transaksi.";
        header("Location: transactions.php");
        exit;
    }
    
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $csrf = filter_input(INPUT_GET, 'csrf', FILTER_DEFAULT);
    
    if ($id && $csrf && $csrf === $_SESSION['csrf_token']) {
        try {
            // Get order_id to clean related tables and write audit log
            $stmtOrder = $pdo->prepare("SELECT order_id FROM transactions WHERE id = ?");
            $stmtOrder->execute([$id]);
            $order_id = $stmtOrder->fetchColumn();
            
            if ($order_id) {
                // Delete transaction
                $stmtDel = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
                $stmtDel->execute([$id]);
                
                // Delete logs and timeline events
                $stmtDelEvents = $pdo->prepare("DELETE FROM payment_events WHERE order_id = ?");
                $stmtDelEvents->execute([$order_id]);
                
                // Log audit action
                $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address) VALUES (?, 'Delete Data', ?, ?)");
                $stmtAudit->execute([$username, "Deleted transaction $order_id and its event history.", $_SERVER['REMOTE_ADDR']]);
                
                $_SESSION['success_message'] = "Transaksi $order_id berhasil dihapus dari sistem.";
            }
        } catch (\Exception $e) {
            $_SESSION['error_message'] = "Gagal menghapus transaksi: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Validasi token keamanan gagal.";
    }
    header("Location: transactions.php");
    exit;
}

// 2. Setup Filters & Search Parameters
$search = isset($_GET['search']) ? trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS)) : '';
$status_filter = isset($_GET['status']) ? trim(filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS)) : '';

$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(order_id LIKE ? OR customer_name LIKE ? OR email LIKE ? OR product_name LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

if ($status_filter !== '') {
    if ($status_filter == 'success') {
        $where_clauses[] = "transaction_status IN ('settlement', 'capture', 'success')";
    } else if ($status_filter == 'failed') {
        $where_clauses[] = "transaction_status IN ('expire', 'deny', 'cancel', 'failed')";
    } else {
        $where_clauses[] = "transaction_status = ?";
        $params[] = $status_filter;
    }
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// 3. Setup Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

try {
    // Total count for pagination
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM transactions $where_sql");
    $stmtCount->execute($params);
    $total_records = $stmtCount->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Fetch transactions
    $stmtTrx = $pdo->prepare("SELECT * FROM transactions $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmtTrx->execute($params);
    $transactions = $stmtTrx->fetchAll();
    
} catch (\Exception $e) {
    die("Error querying database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Transaksi - CloudPay Sandbox</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body>

    <!-- Header / Navbar -->
    <?php require_once '../includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid px-4 my-4">
        
        <div class="d-md-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1"><i class="bi bi-wallet2 text-primary me-2"></i>Daftar Transaksi</h3>
                <p class="text-secondary mb-0">Manajemen records, filter status, timeline log detail, dan export laporan.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="export.php?action=excel<?php echo (!empty($search) ? '&search='.urlencode($search) : '') . (!empty($status_filter) ? '&status='.urlencode($status_filter) : ''); ?>" class="btn btn-outline-custom text-success border-success"><i class="bi bi-file-earmark-excel-fill"></i> Export Excel</a>
                <a href="export.php?action=pdf<?php echo (!empty($search) ? '&search='.urlencode($search) : '') . (!empty($status_filter) ? '&status='.urlencode($status_filter) : ''); ?>" class="btn btn-outline-custom text-danger border-danger"><i class="bi bi-file-earmark-pdf-fill"></i> Export PDF</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="background-color: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #10B981;">
                <strong>Berhasil!</strong> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #EF4444;">
                <strong>Gagal!</strong> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filters & Search Form -->
        <div class="card card-custom p-4 mb-4">
            <form action="transactions.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="form-label form-label-custom">Cari Transaksi</label>
                    <div class="input-group">
                        <span class="input-group-text form-control-custom bg-transparent border-end-0" style="color:var(--text-secondary);"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-custom border-start-0" id="search" name="search" placeholder="Order ID, Pelanggan, Email, Layanan..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label form-label-custom">Filter Status</label>
                    <select class="form-select form-control-custom" id="status" name="status">
                        <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="success" <?php echo $status_filter === 'success' ? 'selected' : ''; ?>>Success / Settlement</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed / Expired / Deny</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-gradient-primary w-100 py-2.5"><i class="bi bi-funnel-fill"></i> Terapkan Filter</button>
                    <a href="transactions.php" class="btn btn-outline-custom w-50 py-2.5">Reset</a>
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="card card-custom mb-4">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Pelanggan</th>
                                <th>Layanan / Produk</th>
                                <th>Nominal</th>
                                <th>Channel</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transactions) == 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-secondary py-5">
                                        <i class="bi bi-inbox fs-1 mb-2 d-block"></i> Tidak ada transaksi ditemukan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $tx): ?>
                                    <tr>
                                        <td class="text-primary fw-semibold"><?php echo htmlspecialchars($tx['order_id']); ?></td>
                                        <td>
                                            <div class="fw-semibold text-white"><?php echo htmlspecialchars($tx['customer_name']); ?></div>
                                            <small class="text-secondary" style="font-size:0.75rem;"><?php echo htmlspecialchars($tx['email']); ?></small>
                                        </td>
                                        <td class="text-secondary"><?php echo htmlspecialchars($tx['product_name']); ?></td>
                                        <td class="fw-semibold text-white">Rp <?php echo number_format($tx['amount'], 0, ',', '.'); ?></td>
                                        <td><span class="badge bg-dark border border-secondary"><?php echo strtoupper(htmlspecialchars($tx['payment_type'] ?: 'N/A')); ?></span></td>
                                        <td>
                                            <span class="badge-status status-<?php echo htmlspecialchars($tx['transaction_status']); ?>">
                                                <?php echo htmlspecialchars($tx['transaction_status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-secondary" style="font-size: 0.85rem;"><?php echo date('d M Y H:i', strtotime($tx['created_at'])); ?></td>
                                        <td style="text-align: center;">
                                            <div class="d-inline-flex gap-1">
                                                <button class="btn btn-outline-custom btn-sm px-2.5 py-1.5" onclick="showTimeline('<?php echo htmlspecialchars($tx['order_id']); ?>')" title="Timeline Log">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>
                                                <?php if ($tx['pdf_invoice_path'] && file_exists(__DIR__ . '/../' . $tx['pdf_invoice_path'])): ?>
                                                    <a href="export.php?action=pdf&order_id=<?php echo urlencode($tx['order_id']); ?>" class="btn btn-outline-custom btn-sm text-danger border-danger px-2.5 py-1.5" title="Download Invoice">
                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($role === 'admin'): ?>
                                                    <a href="transactions.php?action=delete&id=<?php echo $tx['id']; ?>&csrf=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-outline-danger btn-sm px-2.5 py-1.5" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini beserta history log nya?');" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination navigation -->
                <?php if ($total_pages > 1): ?>
                    <nav class="d-flex justify-content-between align-items-center mt-3 px-3">
                        <span class="text-secondary small">Menampilkan data <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_records); ?> dari <?php echo $total_records; ?> entri</span>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link page-link-custom" href="transactions.php?page=<?php echo $page - 1; ?><?php echo (!empty($search) ? '&search='.urlencode($search) : '') . (!empty($status_filter) ? '&status='.urlencode($status_filter) : ''); ?>">Sebelumnya</a>
                            </li>
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <li class="page-item <?php echo $page == $p ? 'active' : ''; ?>">
                                    <a class="page-link page-link-custom" href="transactions.php?page=<?php echo $p; ?><?php echo (!empty($search) ? '&search='.urlencode($search) : '') . (!empty($status_filter) ? '&status='.urlencode($status_filter) : ''); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link page-link-custom" href="transactions.php?page=<?php echo $page + 1; ?><?php echo (!empty($search) ? '&search='.urlencode($search) : '') . (!empty($status_filter) ? '&status='.urlencode($status_filter) : ''); ?>">Selanjutnya</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Timeline Modal -->
    <div class="modal fade" id="timelineModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content card-custom text-light" style="border: 1px solid var(--primary-glow);">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Timeline Transaksi & API Event Log</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="timeline-content">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
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

    <script>
        // Modal logic with ajax loading
        const timelineModal = new bootstrap.Modal(document.getElementById('timelineModal'));
        
        function showTimeline(orderId) {
            const contentDiv = document.getElementById('timeline-content');
            contentDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
            timelineModal.show();
            
            fetch('get_timeline_ajax.php?order_id=' + encodeURIComponent(orderId))
                .then(response => response.text())
                .then(html => {
                    contentDiv.innerHTML = html;
                })
                .catch(error => {
                    contentDiv.innerHTML = '<div class="alert alert-danger">Gagal memuat detail log: ' + error + '</div>';
                });
        }
    </script>
</body>
</html>
