<?php
/**
 * Authentication & Role Authorization Middleware
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect path resolver based on current directory level
$path_prefix = (defined('IS_ADMIN_DIR') && IS_ADMIN_DIR === true) ? '../' : '';

// 1. Enforce user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $path_prefix . "login.php");
    exit;
}

// 2. Enforce Admin access helper
function enforceAdmin() {
    $path_prefix = (defined('IS_ADMIN_DIR') && IS_ADMIN_DIR === true) ? '../' : '';
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $_SESSION['error_message'] = "Akses Ditolak: Anda tidak memiliki hak akses administrator.";
        header("Location: " . $path_prefix . "dashboard.php");
        exit;
    }
}
