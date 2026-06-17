<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';
require_once __DIR__ . '/../config/mail.php';

// Handle email test trigger
$email_result = '';
if (isset($_POST['test_email'])) {
    $target_email = filter_input(INPUT_POST, 'email_address', FILTER_VALIDATE_EMAIL);
    if ($target_email) {
        $debug_log = '';
        try {
            $mail = getPHPMailerInstance();
            
            // Enable raw SMTP debugging to trace the connection logs
            $mail->SMTPDebug = 3;
            $mail->Debugoutput = function($str, $level) use (&$debug_log) {
                $debug_log .= $str . "\n";
            };
            
            $mail->addAddress($target_email, 'Tester CloudPay');
            $mail->Subject = 'Uji Coba Pengiriman SMTP - CloudPay Sandbox';
            $mail->isHTML(true);
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; padding: 20px; background-color: #f7f9fc;">
                <h2 style="color: #6366F1;">Koneksi SMTP Berhasil!</h2>
                <p>Halo, ini adalah email uji coba dari halaman <code>tools/api-test.php</code>.</p>
                <p>Konfigurasi mail server SMTP Anda pada file <code>.env</code> berjalan lancar.</p>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <small style="color: #666;">Dibuat otomatis untuk demonstrasi mata kuliah Virtualisasi Cloud Computing.</small>
            </div>';
            
            if ($mail->send()) {
                $email_result = '<div class="alert alert-success mt-3"><i class="bi bi-check-circle-fill me-1"></i> Email berhasil dikirim ke: <strong>' . htmlspecialchars($target_email) . '</strong>! Silakan cek inbox/spam.</div>';
            } else {
                $email_result = '<div class="alert alert-danger mt-3"><i class="bi bi-x-circle-fill me-1"></i> Gagal mengirim email.</div>';
            }
        } catch (\Exception $e) {
            $email_result = '<div class="alert alert-danger mt-3">';
            $email_result .= '<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Exception:</strong> ' . htmlspecialchars($e->getMessage());
            $email_result .= '<hr><small class="text-start d-block font-monospace" style="font-size:0.75rem; white-space: pre-wrap;"><strong>Log Koneksi SMTP:</strong><br>' . htmlspecialchars($debug_log) . '</small>';
            $email_result .= '</div>';
        }
    } else {
        $email_result = '<div class="alert alert-warning mt-3"><i class="bi bi-exclamation-circle-fill me-1"></i> Masukkan alamat email yang valid!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API & SMTP Testing Tool - CloudPay Sandbox</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-cloud-fill"></i>
                <span class="brand-title">CloudPay Sandbox</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h2 class="display-6 fw-bold">Developer Diagnostic Tools</h2>
                    <p class="text-secondary">Uji fungsionalitas database, SMTP Email, dan Midtrans Sandbox API sebelum deployment final.</p>
                </div>

                <!-- 1. Database Connection Diagnostic -->
                <div class="card card-custom mb-4">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-database text-primary me-2"></i>1. Diagnostik Database</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php
                        try {
                            $tables_stmt = $pdo->query("SHOW TABLES");
                            $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
                            echo '<div class="alert alert-success py-2"><i class="bi bi-check-circle-fill me-1"></i> Koneksi Database PHP-PDO Berhasil!</div>';
                            echo '<p class="mb-1 text-secondary">Tabel yang terdeteksi di database:</p>';
                            echo '<div class="d-flex flex-wrap gap-2 mt-2">';
                            foreach ($tables as $table) {
                                echo '<span class="badge bg-dark border border-secondary px-3 py-2">' . htmlspecialchars($table) . '</span>';
                            }
                            echo '</div>';
                        } catch (\Exception $e) {
                            echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal memuat database: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- 2. Midtrans API Diagnostic -->
                <div class="card card-custom mb-4">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-link-45deg text-primary me-2"></i>2. Koneksi Midtrans API</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php
                        $midConfig = require __DIR__ . '/../config/midtrans.php';
                        if ($midConfig['isConfigured']) {
                            echo '<table class="table table-dark table-striped table-bordered mb-0" style="font-size: 0.9rem;">';
                            echo '<tr><td>Server Key</td><td><code>' . substr($midConfig['serverKey'], 0, 10) . '...</code></td></tr>';
                            echo '<tr><td>Client Key</td><td><code>' . substr($midConfig['clientKey'], 0, 10) . '...</code></td></tr>';
                            echo '<tr><td>Production Mode</td><td>' . ($midConfig['isProduction'] ? 'Yes' : 'No (Sandbox)') . '</td></tr>';
                            echo '</table>';
                            
                            // Try test curl call
                            $ch = curl_init("https://api.sandbox.midtrans.com/v2/charge");
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                            curl_exec($ch);
                            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);
                            
                            if ($http_code == 401 || $http_code == 400 || $http_code == 200) {
                                echo '<div class="alert alert-success mt-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Endpoint Midtrans Sandbox dapat dijangkau (HTTP Code: ' . $http_code . ').</div>';
                            } else {
                                echo '<div class="alert alert-danger mt-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> Midtrans API unreachable (HTTP Code: ' . $http_code . ').</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning py-2 mb-0"><i class="bi bi-exclamation-circle-fill me-1"></i> API Midtrans menggunakan placeholder key. Ganti Server Key di .env untuk menghubungkan.</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- 3. SMTP Email Diagnostic -->
                <div class="card card-custom mb-4">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-envelope-check text-primary me-2"></i>3. Uji Coba SMTP Email (PHPMailer)</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-secondary small">Masukkan email Anda untuk menguji coba pengiriman email dari server web hosting menggunakan template PHPMailer.</p>
                        <form action="api-test.php" method="POST" class="row g-2">
                            <div class="col-md-8">
                                <input type="email" class="form-control form-control-custom" name="email_address" placeholder="email.anda@gmail.com" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="test_email" class="btn btn-gradient-primary w-100"><i class="bi bi-send-fill me-1"></i> Kirim Uji Coba</button>
                            </div>
                        </form>
                        <?php echo $email_result; ?>
                    </div>
                </div>

                <div class="text-center">
                    <a href="../index.php" class="btn btn-outline-custom"><i class="bi bi-arrow-left"></i> Kembali ke Menu Utama</a>
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
