<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Access control check: admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access. Admins only.']);
    exit;
}

header('Content-Type: application/json');

try {
    // Fetch latest 15 callback/snap api logs
    $stmt = $pdo->prepare("SELECT * FROM api_logs ORDER BY created_at DESC LIMIT 15");
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    // Format dates cleanly before sending
    foreach ($logs as &$log) {
        $log['created_at'] = date('d/m/Y H:i:s', strtotime($log['created_at']));
    }
    
    echo json_encode($logs);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
