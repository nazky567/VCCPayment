<?php
/**
 * Modular Dynamic Navbar for CloudPay AI
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$path_prefix = (defined('IS_ADMIN_DIR') && IS_ADMIN_DIR === true) ? '../' : '';
$current_page = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Helper to determine active link class
function getActivePageClass($pageName, $currentPage) {
    return ($pageName === $currentPage) ? 'active' : '';
}
?>
<!-- Centralized Header / Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="<?php echo $path_prefix; ?>index.php">
            <i class="bi bi-cpu-fill text-primary"></i>
            <span class="brand-title fw-bold" style="font-family: 'Outfit', sans-serif; letter-spacing: 0.5px;">CloudPay <span class="text-primary">AI</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($role === 'admin'): ?>
                    <!-- Admin Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('dashboard.php', $current_page); ?>" href="<?php echo $path_prefix; ?>admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('transactions.php', $current_page); ?>" href="<?php echo $path_prefix; ?>admin/transactions.php">
                            <i class="bi bi-wallet2"></i> Kelula Transaksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('callback-monitor.php', $current_page); ?>" href="<?php echo $path_prefix; ?>admin/callback-monitor.php">
                            <i class="bi bi-terminal-dash"></i> Webhook Monitor
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('system-info.php', $current_page); ?>" href="<?php echo $path_prefix; ?>admin/system-info.php">
                            <i class="bi bi-cpu"></i> Info Server
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('export.php', $current_page); ?>" href="<?php echo $path_prefix; ?>admin/export.php">
                            <i class="bi bi-download"></i> Export Data
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('health-check.php', $current_page); ?>" href="<?php echo $path_prefix; ?>health-check.php">
                            <i class="bi bi-heart-pulse"></i> Health Status
                        </a>
                    </li>
                <?php else: ?>
                    <!-- User Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('index.php', $current_page); ?>" href="<?php echo $path_prefix; ?>index.php">
                            <i class="bi bi-house-door"></i> Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('dashboard.php', $current_page); ?>" href="<?php echo $path_prefix; ?>dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard User
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('checkout.php', $current_page); ?>" href="<?php echo $path_prefix; ?>checkout.php">
                            <i class="bi bi-cart3"></i> Checkout
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo getActivePageClass('health-check.php', $current_page); ?>" href="<?php echo $path_prefix; ?>health-check.php">
                            <i class="bi bi-heart-pulse"></i> Health Status
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <div class="navbar-nav ms-auto align-items-center">
                <?php if ($username): ?>
                    <span class="text-secondary me-3" style="font-size:0.9rem;">
                        Halo, <strong class="text-white"><?php echo htmlspecialchars($username); ?></strong> 
                        <span class="badge bg-primary text-uppercase" style="font-size: 0.7rem;"><?php echo htmlspecialchars($role); ?></span>
                    </span>
                    <a href="<?php echo $path_prefix; ?>admin/logout.php" class="btn btn-outline-danger btn-sm py-1.5 px-3">
                        <i class="bi bi-box-arrow-right"></i> Keluar
                    </a>
                <?php else: ?>
                    <a href="<?php echo $path_prefix; ?>login.php" class="btn btn-gradient-primary btn-sm py-1.5 px-3">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
