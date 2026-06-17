<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';

// Disable errors display for cleaner UI output, catch exceptions instead
ini_set('display_errors', 0);

$health_status = true;

// 1. PHP Version check
$php_version = PHP_VERSION;
$php_status = version_compare($php_version, '7.4.0', '>=');
if (!$php_status) $health_status = false;

// 2. PDO Status check
$pdo_status = false;
$pdo_error = '';
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT 1");
        $pdo_status = ($stmt !== false);
    }
} catch (\Exception $e) {
    $pdo_error = $e->getMessage();
    $health_status = false;
}

// 3. OpenSSL check
$openssl_status = extension_loaded('openssl');
if (!$openssl_status) $health_status = false;

// 4. cURL check
$curl_status = extension_loaded('curl');
if (!$curl_status) $health_status = false;

// 5. Midtrans connectivity check
$midtrans_status = false;
$midtrans_msg = '';
$midtransConfig = require __DIR__ . '/config/midtrans.php';

if ($midtransConfig['isConfigured']) {
    $ch = curl_init("https://api.sandbox.midtrans.com/v2/charge");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 401 Unauthorized is expected since we don't send auth headers, but it means API endpoint is reachable
    if ($http_code == 401 || $http_code == 400 || $http_code == 200) {
        $midtrans_status = true;
    } else {
        $midtrans_msg = "Reachable status code: $http_code";
    }
} else {
    $midtrans_msg = "Placeholder keys detected. System is running in Offline Simulation Mode.";
}

// 6. Disk Space Check
$disk_free = disk_free_space(".");
$disk_total = disk_total_space(".");
$disk_pct = 0;
if ($disk_total > 0) {
    $disk_pct = (($disk_total - $disk_free) / $disk_total) * 100;
}

// Helper format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hosting Health Check - CloudPay Sandbox</title>
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
                        <a class="nav-link" href="checkout.php"><i class="bi bi-cart3"></i> Checkout</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="health-check.php"><i class="bi bi-heart-pulse"></i> Health Status</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php"><i class="bi bi-shield-lock"></i> Admin Portal</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="display-6 fw-bold">Cloud Server Health & Connectivity Check</h2>
                    <p class="text-secondary">Informasi spesifikasi virtualisasi environment dan status konektivitas eksternal API.</p>
                </div>

                <div class="card card-custom mb-4">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-activity text-primary me-2"></i>Status Infrastruktur</h4>
                        <div>
                            <?php if ($health_status): ?>
                                <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> SYSTEM NORMAL</span>
                            <?php else: ?>
                                <span class="badge bg-warning px-3 py-2 text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i> ATTENTION REQUIRED</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <!-- PHP & modules status -->
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush" style="background: transparent;">
                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-secondary text-light px-0 py-3">
                                        <div>
                                            <h6 class="mb-0 fw-semibold">Versi PHP</h6>
                                            <small class="text-secondary">Minimal PHP 7.4 untuk libraries</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-dark border border-secondary"><?php echo $php_version; ?></span>
                                            <span class="pulse-indicator <?php echo $php_status ? 'online' : 'offline'; ?>"></span>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-secondary text-light px-0 py-3">
                                        <div>
                                            <h6 class="mb-0 fw-semibold">Ekstensi OpenSSL</h6>
                                            <small class="text-secondary">Dibutuhkan untuk enkripsi payload</small>
                                        </div>
                                        <span class="pulse-indicator <?php echo $openssl_status ? 'online' : 'offline'; ?>"></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-bottom-0 text-light px-0 py-3">
                                        <div>
                                            <h6 class="mb-0 fw-semibold">Ekstensi cURL</h6>
                                            <small class="text-secondary">Dibutuhkan untuk API requests</small>
                                        </div>
                                        <span class="pulse-indicator <?php echo $curl_status ? 'online' : 'offline'; ?>"></span>
                                    </li>
                                </ul>
                            </div>

                            <!-- DB & API status -->
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush" style="background: transparent;">
                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-secondary text-light px-0 py-3">
                                        <div>
                                            <h6 class="mb-0 fw-semibold">Koneksi Database PDO</h6>
                                            <small class="text-secondary">Membaca file konfigurasi .env</small>
                                        </div>
                                        <span class="pulse-indicator <?php echo $pdo_status ? 'online' : 'offline'; ?>"></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-secondary text-light px-0 py-3">
                                        <div>
                                            <h6 class="mb-0 fw-semibold">Konektivitas Midtrans API</h6>
                                            <small class="text-secondary">Tersambung ke sandbox.midtrans.com</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!$midtransConfig['isConfigured']): ?>
                                                <span class="badge bg-warning text-dark" style="font-size:0.7rem;">SIMULATOR OFFLINE</span>
                                            <?php endif; ?>
                                            <span class="pulse-indicator <?php echo $midtrans_status ? 'online' : 'offline'; ?>"></span>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-bottom-0 text-light px-0 py-3">
                                        <div>
                                            <h6 class="mb-0 fw-semibold">Penyimpanan Server</h6>
                                            <small class="text-secondary">Menyimpan invoice PDF & logs</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-dark border border-secondary"><?php echo round($disk_pct, 1); ?>% digunakan</span>
                                            <span class="pulse-indicator online"></span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Disk space details card -->
                <div class="card card-custom mb-4">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-hdd-network text-secondary me-2"></i>Status Penggunaan Disk Space</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sisa Ruang: <strong><?php echo formatBytes($disk_free); ?></strong></span>
                            <span>Kapasitas Total: <strong><?php echo formatBytes($disk_total); ?></strong></span>
                        </div>
                        <div class="progress" style="height: 12px; background-color: rgba(255, 255, 255, 0.05);">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $disk_pct; ?>%;" aria-valuenow="<?php echo $disk_pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <a href="index.php" class="btn btn-outline-custom"><i class="bi bi-arrow-left"></i> Kembali ke Halaman Utama</a>
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
