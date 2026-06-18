<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: ../dashboard.php");
    }
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] === 'admin') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // Log audit action
                    $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address) VALUES (?, 'Login', 'Successful admin panel login', ?)");
                    $stmtAudit->execute([$username, $_SERVER['REMOTE_ADDR']]);

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Akses ditolak. Anda tidak memiliki hak akses administrator.';
                }
            } else {
                $error = 'Username atau password salah.';
            }
        } catch (\Exception $e) {
            $error = 'Terjadi kesalahan sistem database.';
            error_log("Login failure exception: " . $e->getMessage());
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
    <title>Login Admin - CloudPay AI</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh; padding: 20px;">

    <!-- Card Login -->
    <div class="card card-custom w-100" style="max-width: 420px;">
        <div class="card-header-custom text-center py-4">
            <h3 class="navbar-brand justify-content-center mb-1">
                <i class="bi bi-cpu-fill text-primary me-2"></i>
                <span class="brand-title">CloudPay AI</span>
            </h3>
            <p class="text-secondary small mb-0">Portal Manajemen Langganan AI</p>
        </div>
        <div class="card-body p-4">
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
                        <input type="text" class="form-control form-control-custom border-start-0" id="username" name="username" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label form-label-custom">Password</label>
                    <div class="input-group">
                        <span class="input-group-text form-control-custom bg-transparent border-end-0" style="color: var(--text-secondary);"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control form-control-custom border-start-0" id="password" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit" name="login" class="btn btn-gradient-primary w-100 py-2.5 mb-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Dashboard
                </button>
            </form>

            <div class="text-center mt-3 pt-3 border-top border-secondary">
                <a href="../login.php" class="text-secondary small text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke Gateway Utama</a>
            </div>
        </div>
    </div>

</body>
</html>
