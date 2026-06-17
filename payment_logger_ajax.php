<?php
session_start();
require_once __DIR__ . '/config/database.php';

$order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_SPECIAL_CHARS);
$event_name = filter_input(INPUT_POST, 'event_name', FILTER_SANITIZE_SPECIAL_CHARS);
$event_data = filter_input(INPUT_POST, 'event_data', FILTER_DEFAULT);

if ($order_id && $event_name) {
    try {
        $stmt = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data) VALUES (?, ?, ?)");
        $stmt->execute([$order_id, $event_name, $event_data]);
        echo json_encode(['status' => 'success']);
    } catch (\Exception $e) {
        error_log("AJAX payment logger failure: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid arguments']);
}
