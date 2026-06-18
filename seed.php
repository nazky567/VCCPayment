<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Verify DB
if (!isset($pdo)) {
    die("Database object not instantiated.");
}

// Reset tables for a clean academic demo environment
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE transactions;");
    $pdo->exec("TRUNCATE TABLE payment_events;");
    $pdo->exec("TRUNCATE TABLE api_logs;");
    $pdo->exec("TRUNCATE TABLE audit_logs;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
} catch (\Exception $e) {
    die("Gagal mereset tabel database: " . $e->getMessage());
}

// Fetch ID of user with username 'user' to link mock transactions
$user_id = null;
try {
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = 'user' LIMIT 1");
    $stmtUser->execute();
    $user_id = $stmtUser->fetchColumn() ?: null;
} catch (\Exception $e) {
    error_log("Failed to fetch user_id in seeder: " . $e->getMessage());
}

$names = ['Budi Santoso', 'Siti Rahma', 'Dewi Lestari', 'Agus Prayogo', 'Andi Wijaya', 'Eko Susilo', 'Rina Melati', 'Hendra Gunawan', 'Mega Utami', 'Rian Hidayat', 'Ahmad Fauzi', 'Sari Indah', 'Joko Widodo', 'Fajar Pratama', 'Putri Ayu', 'Rizky Ramadhan', 'Denny Siregar', 'Wawan Kurniawan', 'Lutfi Hakim', 'Diana Putri'];
$products = [
    ['name' => 'ChatGPT Plus 1 Bulan', 'price' => 320000],
    ['name' => 'Claude Pro 1 Bulan', 'price' => 320000],
    ['name' => 'Gemini Advanced 1 Bulan', 'price' => 300000],
    ['name' => 'Perplexity Pro 1 Bulan', 'price' => 300000],
    ['name' => 'ChatGPT Plus + Claude Pro (Bundle)', 'price' => 600000]
];
$statuses = ['settlement', 'pending', 'expire', 'deny', 'cancel'];
$payment_types = ['credit_card', 'bank_transfer', 'qris', 'gopay', 'shopeepay'];

$count = 50;
$inserted = 0;

try {
    for ($i = 0; $i < $count; $i++) {
        $name = $names[array_rand($names)];
        $prod = $products[array_rand($products)];
        
        // Status ratio: ~70% success (settlement), ~15% pending, ~15% failed
        $rand = rand(1, 10);
        if ($rand <= 7) {
            $status = 'settlement';
        } else if ($rand <= 8) {
            $status = 'pending';
        } else {
            $status = $statuses[array_rand([2, 3, 4])]; // expire, deny, cancel
        }
        
        $payment_type = $payment_types[array_rand($payment_types)];
        $amount = $prod['price'];
        $product_name = $prod['name'];
        $phone = '08' . rand(11111111, 99999999);
        $email = strtolower(str_replace(' ', '.', $name)) . rand(10, 99) . '@example.com';
        
        // Date within the last 30 days
        $days_ago = rand(0, 30);
        $date = date('Y-m-d H:i:s', strtotime("-$days_ago days -" . rand(0, 23) . " hours -" . rand(0, 59) . " minutes"));
        $order_id = 'ORD-' . date('Ymd', strtotime($date)) . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $snap_token = 'MOCK-SNAP-TOKEN-' . bin2hex(random_bytes(16));
        
        // Insert Transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (order_id, user_id, customer_name, email, phone, product_name, amount, payment_type, transaction_status, transaction_time, snap_token, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $order_id, $user_id, $name, $email, $phone, $product_name, $amount, 
            $status !== 'pending' ? $payment_type : null, $status, 
            $status == 'settlement' ? $date : null, $snap_token, $date, $date
        ]);
        
        // Insert events timeline
        // Event 1: Created
        $stmtEvt = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data, created_at) VALUES (?, 'Transaction Created', ?, ?)");
        $stmtEvt->execute([$order_id, json_encode(['customer' => $name, 'amount' => $amount, 'product' => $product_name]), $date]);
        
        // Event 2: Snap Token Generated
        $snapDate = date('Y-m-d H:i:s', strtotime($date . ' + 45 seconds'));
        $stmtEvt2 = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data, created_at) VALUES (?, 'Snap Token Generated', ?, ?)");
        $stmtEvt2->execute([$order_id, json_encode(['token' => $snap_token]), $snapDate]);
        
        // Event 3: Payment processed/updated callback simulation
        if ($status !== 'pending') {
            $callbackDate = date('Y-m-d H:i:s', strtotime($date . ' + 3 minutes'));
            
            $stmtEvt3 = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data, created_at) VALUES (?, ?, ?, ?)");
            $stmtEvt3->execute([
                $order_id, 
                "Status Updated: " . strtoupper($status), 
                json_encode([
                    'payment_type' => $payment_type, 
                    'gross_amount' => $amount,
                    'transaction_status' => $status
                ]), 
                $callbackDate
            ]);
            
            // Event 4: Email Receipt
            $emailDate = date('Y-m-d H:i:s', strtotime($date . ' + 4 minutes'));
            $stmtEvt4 = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data, created_at) VALUES (?, 'Email Notification Sent', ?, ?)");
            $stmtEvt4->execute([$order_id, json_encode(['email' => $email, 'status' => strtoupper($status)]), $emailDate]);
        }
        
        $inserted++;
    }
    
    // Seed system audit trail logs
    $audit_actions = ['Login', 'Logout', 'Export PDF', 'Export Excel', 'Filter Transactions', 'View Dashboard'];
    $audit_users = ['admin', 'user'];
    
    for ($j = 0; $j < 20; $j++) {
        $act = $audit_actions[array_rand($audit_actions)];
        $usr = $audit_users[array_rand($audit_users)];
        $date = date('Y-m-d H:i:s', strtotime("-" . rand(0, 15) . " days -" . rand(0, 23) . " hours"));
        $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmtAudit->execute([$usr, $act, "User $usr performed action '$act' in system panel.", '127.0.0.1', $date]);
    }
    
} catch (\Exception $e) {
    die("Kesalahan seeding data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeding - CloudPay Sandbox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container my-5 text-center">
        <div class="card card-custom max-width-600 mx-auto p-5">
            <div class="mb-4">
                <span class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle" style="width: 80px; height: 80px; font-size: 2.5rem;">
                    <i class="bi bi-database-check"></i>
                </span>
            </div>
            <h2 class="text-success fw-bold">Database Seeding Berhasil!</h2>
            <p class="text-secondary my-3">
                Telah berhasil memasukkan <strong><?php echo $inserted; ?></strong> data transaksi dummy lengkap dengan log timeline dan audit trail untuk presentasi dashboard.
            </p>
            <div class="d-flex justify-content-center gap-2 mt-4">
                <a href="admin/dashboard.php" class="btn btn-gradient-primary">Buka Dashboard Admin</a>
                <a href="checkout.php" class="btn btn-outline-custom">Kembali ke Checkout</a>
            </div>
        </div>
    </div>
</body>
</html>
