<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';

// Enforce login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


// Validate CSRF
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Token keamanan CSRF tidak valid. Silakan coba lagi.";
    header("Location: checkout.php");
    exit;
}

// Clean and validate inputs
$customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_SPECIAL_CHARS);
$customer_email = filter_input(INPUT_POST, 'customer_email', FILTER_VALIDATE_EMAIL);
$customer_phone = filter_input(INPUT_POST, 'customer_phone', FILTER_SANITIZE_SPECIAL_CHARS);
$product_name = filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_SPECIAL_CHARS);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

if (!$customer_name || !$customer_email || !$customer_phone || !$product_name || !$amount || $amount <= 0) {
    $_SESSION['error_message'] = "Informasi input form tidak valid. Pastikan semua field terisi dengan benar.";
    header("Location: checkout.php");
    exit;
}

// Generate Order ID
$order_id = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

try {
    // Database Insert Transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (order_id, user_id, customer_name, email, phone, product_name, amount, transaction_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$order_id, $_SESSION['user_id'], $customer_name, $customer_email, $customer_phone, $product_name, $amount]);
    
    // Log Timeline Event
    $stmtEvent = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data) VALUES (?, 'Transaction Created', ?)");
    $stmtEvent->execute([$order_id, json_encode([
        'customer' => $customer_name,
        'email' => $customer_email,
        'product' => $product_name,
        'amount' => $amount,
        'ip' => $_SERVER['REMOTE_ADDR']
    ])]);

    $snap_token = '';
    
    // Check if Midtrans Configured and composer exists
    $midtransConfig = require __DIR__ . '/config/midtrans.php';
    
    if (class_exists('\Midtrans\Snap') && $midtransConfig['isConfigured']) {
        // Build Midtrans Payload
        $transaction_details = [
            'order_id' => $order_id,
            'gross_amount' => (int)$amount,
        ];
        
        $customer_details = [
            'first_name'    => $customer_name,
            'email'         => $customer_email,
            'phone'         => $customer_phone,
        ];
        
        $item_details = [
            [
                'id' => 'item1',
                'price' => (int)$amount,
                'quantity' => 1,
                'name' => strlen($product_name) > 50 ? substr($product_name, 0, 47) . '...' : $product_name
            ]
        ];
        
        $payload = [
            'transaction_details' => $transaction_details,
            'customer_details' => $customer_details,
            'item_details' => $item_details,
            'credit_card' => [
                'secure' => true
            ]
        ];

        // Request Token
        $request_body = json_encode($payload);
        
        try {
            $snap_token = \Midtrans\Snap::getSnapToken($payload);
            $response_body = json_encode(['token' => $snap_token]);
            
            // Save snap_token to DB
            $stmtUpdate = $pdo->prepare("UPDATE transactions SET snap_token = ? WHERE order_id = ?");
            $stmtUpdate->execute([$snap_token, $order_id]);
            
            // Log API request
            $stmtLog = $pdo->prepare("INSERT INTO api_logs (endpoint, request_body, response_body) VALUES (?, ?, ?)");
            $stmtLog->execute(['/snap/v1/transactions', $request_body, $response_body]);
            
            // Log Timeline Event
            $stmtEvent = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data) VALUES (?, 'Snap Token Generated', ?)");
            $stmtEvent->execute([$order_id, $response_body]);
            
        } catch (\Exception $e) {
            error_log("Midtrans Snap Exception: " . $e->getMessage());
            // Log API failure
            $stmtLog = $pdo->prepare("INSERT INTO api_logs (endpoint, request_body, response_body) VALUES (?, ?, ?)");
            $stmtLog->execute(['/snap/v1/transactions (Error)', $request_body, json_encode(['error' => $e->getMessage()])]);
            
            // Generate mock token as fallback
            $snap_token = 'MOCK-SNAP-TOKEN-' . bin2hex(random_bytes(16));
        }
    }
    
    // If snap_token is empty (e.g. composer missing, or keys not set), generate a mock token for development
    if (empty($snap_token)) {
        $snap_token = 'MOCK-SNAP-TOKEN-' . bin2hex(random_bytes(16));
        $stmtUpdate = $pdo->prepare("UPDATE transactions SET snap_token = ? WHERE order_id = ?");
        $stmtUpdate->execute([$snap_token, $order_id]);
        
        $stmtEvent = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data) VALUES (?, 'Mock Snap Token Generated', ?)");
        $stmtEvent->execute([$order_id, json_encode([
            'info' => 'Midtrans keys are placeholders or Composer is not installed yet. System generated sandbox simulation mock token.',
            'token' => $snap_token
        ])]);
    }
    
    // Redirect to payment.php
    header("Location: payment.php?order_id=" . urlencode($order_id));
    exit;

} catch (\Exception $e) {
    error_log("Checkout Process Failure: " . $e->getMessage());
    $_SESSION['error_message'] = "Terjadi kesalahan sistem database: " . $e->getMessage();
    header("Location: checkout.php");
    exit;
}
