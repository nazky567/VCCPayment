<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['username'])) {
    try {
        $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address) VALUES (?, 'Logout', 'User logged out of admin panel', ?)");
        $stmtAudit->execute([$_SESSION['username'], $_SERVER['REMOTE_ADDR']]);
    } catch (\Exception $e) {
        error_log("Failed to log logout action: " . $e->getMessage());
    }
}

// Clear session
session_unset();
session_destroy();

header("Location: ../login.php");
exit;
