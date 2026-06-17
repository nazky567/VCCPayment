<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/config/payment_helpers.php';


// Set response header
header('Content-Type: application/json');

// Read JSON input from Midtrans webhook
$raw_notification = file_get_contents('php://input');
$notification = json_decode($raw_notification, true);

if (!$notification) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

// Log incoming API request
try {
    $stmtLog = $pdo->prepare("INSERT INTO api_logs (endpoint, request_body, response_body) VALUES (?, ?, ?)");
    $stmtLog->execute(['/notification-callback', $raw_notification, json_encode(['status' => 'received'])]);
} catch (\Exception $e) {
    error_log("Failed to log notification API request: " . $e->getMessage());
}

// Variables from notification
$order_id = isset($notification['order_id']) ? $notification['order_id'] : '';
$transaction_status = isset($notification['transaction_status']) ? $notification['transaction_status'] : '';
$payment_type = isset($notification['payment_type']) ? $notification['payment_type'] : '';
$gross_amount = isset($notification['gross_amount']) ? $notification['gross_amount'] : '';
$status_code = isset($notification['status_code']) ? $notification['status_code'] : '';
$signature_key = isset($notification['signature_key']) ? $notification['signature_key'] : '';

// 1. Signature Key Verification (Bypass only if mock_simulation is true for demonstration)
$is_mock = isset($notification['mock_simulation']) && $notification['mock_simulation'] === true;

if (!$is_mock) {
    $server_key = getenv('MIDTRANS_SERVER_KEY') ?: '';
    // Formula: SHA512(order_id + status_code + gross_amount + server_key)
    // Gross amount might be formatted differently, Midtrans returns string. Check decimals or round.
    $gross_amount_rounded = number_format((double)$gross_amount, 2, '.', '');
    // Alternatively, Midtrans sends integer format sometimes or string. We can try formatting gross_amount properly:
    // Some Midtrans responses don't include decimals, others do. Let's build both checks.
    $gross_amount_int = (int)$gross_amount;
    
    $local_signature_1 = hash("sha512", $order_id . $status_code . $gross_amount . $server_key);
    $local_signature_2 = hash("sha512", $order_id . $status_code . $gross_amount_rounded . $server_key);
    $local_signature_3 = hash("sha512", $order_id . $status_code . $gross_amount_int . $server_key);
    
    if ($signature_key !== $local_signature_1 && $signature_key !== $local_signature_2 && $signature_key !== $local_signature_3) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Signature key verification failed']);
        exit;
    }
}

try {
    // Check if transaction exists
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $trx = $stmt->fetch();
    
    if (!$trx) {
        http_response_code(444);
        echo json_encode(['status' => 'error', 'message' => 'Transaction not found in database']);
        exit;
    }

    // Determine final payment status for DB
    $db_status = 'pending';
    if ($transaction_status == 'capture') {
        if ($payment_type == 'credit_card') {
            // Check challenge status
            $fraud = isset($notification['fraud_status']) ? $notification['fraud_status'] : '';
            if ($fraud == 'challenge') {
                $db_status = 'challenge';
            } else {
                $db_status = 'settlement';
            }
        }
    } else if ($transaction_status == 'settlement') {
        $db_status = 'settlement';
    } else if ($transaction_status == 'pending') {
        $db_status = 'pending';
    } else if ($transaction_status == 'deny') {
        $db_status = 'deny';
    } else if ($transaction_status == 'expire') {
        $db_status = 'expire';
    } else if ($transaction_status == 'cancel') {
        $db_status = 'cancel';
    } else {
        $db_status = $transaction_status;
    }

    // Update transaction in DB
    $stmtUpdate = $pdo->prepare("UPDATE transactions SET transaction_status = ?, payment_type = ?, transaction_time = NOW() WHERE order_id = ?");
    $stmtUpdate->execute([$db_status, $payment_type, $order_id]);
    
    // Log Timeline Event
    $stmtEvent = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data) VALUES (?, ?, ?)");
    $stmtEvent->execute([$order_id, "Status Updated: " . strtoupper($db_status), json_encode($notification)]);

    // Check if status is successful to generate Invoice PDF and send email
    $is_success = ($db_status == 'settlement' || $db_status == 'capture');
    
    // Generate Invoice PDF
    $pdfPath = '';
    if ($is_success) {
        $pdfPath = generateInvoicePDF($trx, $db_status, $payment_type);
        if ($pdfPath) {
            $stmtUpdatePdf = $pdo->prepare("UPDATE transactions SET pdf_invoice_path = ? WHERE order_id = ?");
            $stmtUpdatePdf->execute([$pdfPath, $order_id]);
        }
    }

    // Send Email notification to customer
    sendNotificationEmail($trx, $db_status, $pdfPath);

    // Audit Log for notification update
    $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmtAudit->execute([
        $is_mock ? 'Mock_Simulator' : 'Midtrans_Webhook',
        'Update Transaction Status',
        "Transaction $order_id updated to $db_status. Method: $payment_type.",
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Notification processed successfully', 'new_status' => $db_status]);

} catch (\Exception $e) {
    error_log("Callback process failure: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Shared helpers are loaded from config/payment_helpers.php

