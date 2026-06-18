<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';

if (isset($_POST['login_user'])) {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Check password and verify role is 'user'
            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] === 'user') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // Log audit action
                    $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address) VALUES (?, 'Login', 'Successful user portal login', ?)");
                    $stmtAudit->execute([$username, $_SERVER['REMOTE_ADDR']]);

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Akses ditolak. Silakan gunakan portal admin untuk login administrator.';
                }
            } else {
                $error = 'Username atau password salah.';
            }
        } catch (\Exception $e) {
            $error = 'Terjadi kesalahan sistem database.';
            error_log("User login failure exception: " . $e->getMessage());
        }
    } else {
        $error = 'Silakan masukkan username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Gateway - CloudPay AI</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .gate-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .gate-card {
            background: rgba(30, 41, 59, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            width: 100%;
            max-width: 850px;
        }
        .gate-divider {
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }
        @media (max-width: 767.98px) {
            .gate-divider {
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                margin-bottom: 25px;
                padding-bottom: 25px;
            }
        }
        .admin-card-promo {
            background: rgba(99, 102, 241, 0.04);
            border: 1px dashed rgba(99, 102, 241, 0.25);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>

    <div class="gate-container">
        <div class="gate-card">
            <!-- Header Brand -->
            <div class="text-center py-4 border-bottom border-secondary" style="background: rgba(15, 23, 42, 0.25);">
                <h3 class="navbar-brand justify-content-center mb-1">
                    <i class="bi bi-cpu-fill text-primary me-2"></i>
                    <span class="brand-title">CloudPay AI Portal</span>
                </h3>
                <p class="text-secondary small mb-0">Platform Penjualan Langganan AI Premium & Cloud Sandbox</p>
            </div>
            
            <div class="row g-0 p-4 p-md-5">
                <!-- User Login Form -->
                <div class="col-md-6 pe-md-4 gate-divider">
                    <div class="pe-md-2">
                        <h4 class="text-white fw-bold mb-3"><i class="bi bi-person-fill text-primary me-2"></i>Login Sebagai User</h4>
                        <p class="text-secondary small mb-4">Akses dashboard user Anda untuk membuat simulasi pembayaran dan mengunduh invoice PDF.</p>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger py-2" role="alert" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #EF4444; font-size: 0.85rem;">
                                <i class="bi bi-exclamation-circle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label form-label-custom">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text form-control-custom bg-transparent border-end-0" style="color: var(--text-secondary);"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control form-control-custom border-start-0" id="username" name="username" placeholder="Username (cth: user)" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label form-label-custom">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text form-control-custom bg-transparent border-end-0" style="color: var(--text-secondary);"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control form-control-custom border-start-0" id="password" name="password" placeholder="Password (cth: user123)" required>
                                </div>
                            </div>

                            <button type="submit" name="login_user" class="btn btn-gradient-primary w-100 py-2.5">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Dashboard User
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Admin Portal Link -->
                <div class="col-md-6 ps-md-4 d-flex flex-column justify-content-center">
                    <div class="ps-md-2 h-100">
                        <div class="admin-card-promo">
                            <div class="fs-1 text-primary mb-3">
                                <i class="bi bi-shield-lock-fill" style="filter: drop-shadow(0 0 10px rgba(99, 102, 241, 0.3));"></i>
                            </div>
                            <h4 class="text-white fw-bold mb-2">Admin Portal</h4>
                            <p class="text-secondary small mb-4 col-11 text-center">
                                Khusus untuk Administrator dalam mengelola audit log, monitoring live webhook callback, memantau infrastruktur virtualisasi server, dan ekspor laporan transaksi global.
                            </p>
                            <a href="admin/login.php" class="btn btn-outline-custom w-100 py-2.5">
                                <i class="bi bi-door-open me-1"></i> Masuk Portal Admin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
