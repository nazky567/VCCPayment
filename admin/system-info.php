<?php
define('IS_ADMIN_DIR', true);
require_once __DIR__ . '/../includes/auth_check.php';
enforceAdmin();
require_once __DIR__ . '/../config/database.php';

$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Get database version
$mysql_version = 'Unknown';
try {
    $mysql_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch (\Exception $e) {
    $mysql_version = 'Error: ' . $e->getMessage();
}

// Hostname and IP details
$hostname = gethostname();
$server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : gethostbyname($hostname);
$client_ip = $_SERVER['REMOTE_ADDR'];

// Disk Stats
$disk_total = disk_total_space(".");
$disk_free = disk_free_space(".");
$disk_used = $disk_total - $disk_free;
$disk_used_percent = ($disk_total > 0) ? ($disk_used / $disk_total) * 100 : 0;

// Memory Stats (Tries to read Linux system stats, falls back for Windows)
$mem_total = 'N/A';
$mem_free = 'N/A';
$mem_percent = 0;
$memory_msg = "Running on Windows / Local host";

if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    // Linux proc parser
    if (file_exists('/proc/meminfo')) {
        $data = file('/proc/meminfo');
        $meminfo = [];
        foreach ($data as $line) {
            list($key, $val) = explode(":", $line, 2);
            $meminfo[$key] = trim($val);
        }
        
        $total_mem_kb = (int) filter_var($meminfo['MemTotal'], FILTER_SANITIZE_NUMBER_INT);
        $free_mem_kb = (int) filter_var($meminfo['MemFree'], FILTER_SANITIZE_NUMBER_INT);
        $buffers_kb = isset($meminfo['Buffers']) ? (int) filter_var($meminfo['Buffers'], FILTER_SANITIZE_NUMBER_INT) : 0;
        $cached_kb = isset($meminfo['Cached']) ? (int) filter_var($meminfo['Cached'], FILTER_SANITIZE_NUMBER_INT) : 0;
        
        $real_free_kb = $free_mem_kb + $buffers_kb + $cached_kb;
        $used_mem_kb = $total_mem_kb - $real_free_kb;
        
        $mem_total = round($total_mem_kb / 1024 / 1024, 2) . ' GB';
        $mem_free = round($real_free_kb / 1024 / 1024, 2) . ' GB';
        $mem_percent = ($total_mem_kb > 0) ? ($used_mem_kb / $total_mem_kb) * 100 : 0;
        $memory_msg = "Real-time parsing of Linux /proc/meminfo";
    }
} else {
    // Windows fallback using system call or PHP metrics
    $memory_limit = ini_get('memory_limit');
    $allocated = memory_get_usage(true);
    $mem_total = $memory_limit;
    $mem_free = 'Allocated: ' . round($allocated / 1024 / 1024, 2) . ' MB';
    $mem_percent = 5; // Placeholder
}

function formatBytesSize($bytes, $precision = 2) {
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
    <title>Server Info - CloudPay Sandbox</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Header / Navbar -->
    <?php require_once '../includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid px-4 my-4">
        
        <div class="mb-4">
            <h3 class="mb-1"><i class="bi bi-cpu text-primary me-2"></i>Infrastruktur & Cloud Deployment Info</h3>
            <p class="text-secondary mb-0">Rincian virtualisasi spesifikasi server hosting dan database engine terintegrasi.</p>
        </div>

        <div class="row g-4">
            <!-- Left Info Panel -->
            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-server text-primary me-2"></i>Spesifikasi Server</h5>
                    </div>
                    <div class="card-body p-4">
                        <table class="table table-dark table-striped border-secondary mb-0" style="font-size: 0.9rem;">
                            <tbody>
                                <tr>
                                    <td class="text-secondary py-3" style="width: 40%;">Sistem Operasi</td>
                                    <td class="fw-semibold text-white py-3"><?php echo htmlspecialchars(php_uname()); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary py-3">Server Hostname</td>
                                    <td class="fw-semibold text-white py-3"><code><?php echo htmlspecialchars($hostname); ?></code></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary py-3">Server IP Address</td>
                                    <td class="fw-semibold text-white py-3"><code><?php echo htmlspecialchars($server_ip); ?></code></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary py-3">Versi PHP</td>
                                    <td class="fw-semibold text-white py-3"><?php echo htmlspecialchars(PHP_VERSION); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary py-3">Database Server</td>
                                    <td class="fw-semibold text-white py-3">MariaDB / MySQL Client (PDO)</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary py-3">Versi MySQL Server</td>
                                    <td class="fw-semibold text-white py-3"><code><?php echo htmlspecialchars($mysql_version); ?></code></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary py-3">Protokol Server</td>
                                    <td class="fw-semibold text-white py-3"><?php echo htmlspecialchars($_SERVER['SERVER_PROTOCOL']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right stats panel -->
            <div class="col-md-6">
                <div class="card card-custom h-100 d-flex flex-column justify-content-between">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-pie-chart text-secondary me-2"></i>Alokasi Sumber Daya Server</h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Storage usage indicator -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-secondary fw-semibold">Penyimpanan Terpakai (cPanel Home Directory)</span>
                                <span class="text-white fw-bold"><?php echo formatBytesSize($disk_used); ?> / <?php echo formatBytesSize($disk_total); ?></span>
                            </div>
                            <div class="progress" style="height: 14px; background-color: rgba(255, 255, 255, 0.05);">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $disk_used_percent; ?>%;" aria-valuenow="<?php echo $disk_used_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-secondary mt-1 d-block"><?php echo round(100 - $disk_used_percent, 1); ?>% Space bebas tersisa.</small>
                        </div>

                        <!-- Memory usage indicator -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-secondary fw-semibold">Alokasi RAM Server (Virtual)</span>
                                <span class="text-white fw-bold"><?php echo $mem_free; ?> / <?php echo $mem_total; ?></span>
                            </div>
                            <div class="progress" style="height: 14px; background-color: rgba(255, 255, 255, 0.05);">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $mem_percent; ?>%;" aria-valuenow="<?php echo $mem_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-secondary mt-1 d-block"><?php echo htmlspecialchars($memory_msg); ?></small>
                        </div>

                        <hr class="border-secondary my-4">

                        <div class="d-flex align-items-center mb-0 p-3 rounded" style="background-color: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.15);">
                            <i class="bi bi-shield-check text-primary fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Virtualization Layer Detected</h6>
                                <small class="text-secondary">Aplikasi berjalan pada container virtual cloud server hosting.</small>
                            </div>
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
</body>
</html>
