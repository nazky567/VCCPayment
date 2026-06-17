<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Access control check: admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<div class="alert alert-danger mb-0">Akses Ditolak. Halaman ini hanya untuk administrator.</div>';
    exit;
}

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$order_id) {
    echo '<div class="alert alert-warning mb-0">Order ID tidak didefinisikan.</div>';
    exit;
}

try {
    // Get transaction details first
    $stmtTrx = $pdo->prepare("SELECT * FROM transactions WHERE order_id = ?");
    $stmtTrx->execute([$order_id]);
    $trx = $stmtTrx->fetch();

    if (!$trx) {
        echo '<div class="alert alert-danger mb-0">Transaksi tidak ditemukan dalam database.</div>';
        exit;
    }

    // Get events list
    $stmtEv = $pdo->prepare("SELECT * FROM payment_events WHERE order_id = ? ORDER BY created_at ASC");
    $stmtEv->execute([$order_id]);
    $events = $stmtEv->fetchAll();

    // Get api request/response logs related to this order_id
    // Since api_logs has raw JSON body, search for order_id inside request_body
    $stmtApi = $pdo->prepare("SELECT * FROM api_logs WHERE request_body LIKE ? ORDER BY created_at ASC");
    $stmtApi->execute(["%$order_id%"]);
    $api_logs = $stmtApi->fetchAll();
    
    // Render HTML Output
    ?>
    <div class="row g-3 mb-4 text-start">
        <div class="col-md-6">
            <h6 class="text-secondary small mb-1">Identitas Pelanggan</h6>
            <p class="mb-0 fw-semibold text-white"><?php echo htmlspecialchars($trx['customer_name']); ?></p>
            <small class="text-secondary"><?php echo htmlspecialchars($trx['email']); ?> | <?php echo htmlspecialchars($trx['phone']); ?></small>
        </div>
        <div class="col-md-6 text-md-end">
            <h6 class="text-secondary small mb-1">Status Pembayaran</h6>
            <div>
                <span class="badge-status status-<?php echo htmlspecialchars($trx['transaction_status']); ?>">
                    <?php echo htmlspecialchars($trx['transaction_status']); ?>
                </span>
            </div>
            <small class="text-secondary">Produk: <?php echo htmlspecialchars($trx['product_name']); ?></small>
        </div>
    </div>

    <hr class="border-secondary my-3">

    <div class="row g-3">
        <!-- Event Timeline -->
        <div class="col-md-6 border-end border-secondary">
            <h6 class="text-secondary uppercase small mb-3"><i class="bi bi-clock-history"></i> Flow Timeline Kejadian</h6>
            <?php if (count($events) == 0): ?>
                <p class="text-secondary small">Belum ada timeline log terdaftar.</p>
            <?php else: ?>
                <ul class="timeline text-start mb-0">
                    <?php foreach ($events as $ev): 
                        // Categorize style based on event name
                        $statusClass = 'pending';
                        if (strpos(strtolower($ev['event_name']), 'settlement') !== false || strpos(strtolower($ev['event_name']), 'success') !== false) {
                            $statusClass = 'success';
                        } elseif (strpos(strtolower($ev['event_name']), 'fail') !== false || strpos(strtolower($ev['event_name']), 'expire') !== false || strpos(strtolower($ev['event_name']), 'cancel') !== false || strpos(strtolower($ev['event_name']), 'deny') !== false) {
                            $statusClass = 'failed';
                        }
                    ?>
                        <li class="timeline-item <?php echo $statusClass; ?>">
                            <div class="timeline-time"><?php echo date('d M Y - H:i:s', strtotime($ev['created_at'])); ?> WIB</div>
                            <div class="timeline-title text-white"><?php echo htmlspecialchars($ev['event_name']); ?></div>
                            <?php if (!empty($ev['event_data'])): ?>
                                <div class="timeline-body text-secondary mt-1">
                                    <?php 
                                    $data = json_decode($ev['event_data'], true);
                                    if ($data) {
                                        // Print cleaner key-value details for presentation
                                        foreach ($data as $key => $val) {
                                            if (is_array($val)) $val = json_encode($val);
                                            // Shorten snap tokens
                                            if ($key === 'token') $val = substr($val, 0, 10) . '...';
                                            echo '<span class="d-block text-truncate"><strong>' . htmlspecialchars($key) . '</strong>: ' . htmlspecialchars($val) . '</span>';
                                        }
                                    } else {
                                        echo htmlspecialchars($ev['event_data']);
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- API Request/Response logs -->
        <div class="col-md-6 text-start">
            <h6 class="text-secondary uppercase small mb-3"><i class="bi bi-terminal-dash"></i> API Integration Payload Logs</h6>
            <?php if (count($api_logs) == 0): ?>
                <p class="text-secondary small">Tidak ada HTTP Request/Response log yang terekam.</p>
            <?php else: ?>
                <div class="accordion accordion-flush" id="apiLogsAccordion" style="--bs-accordion-bg: transparent; --bs-accordion-color: var(--text-primary); --bs-accordion-btn-color: var(--text-primary); --bs-accordion-border-color: var(--border-color);">
                    <?php foreach ($api_logs as $index => $log): ?>
                        <div class="accordion-item" style="border-bottom: 1px solid var(--border-color);">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed px-0 py-2.5 bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-<?php echo $index; ?>" aria-expanded="false" style="box-shadow: none;">
                                    <div class="text-truncate">
                                        <span class="badge bg-secondary me-2"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></span>
                                        <code class="text-primary"><?php echo htmlspecialchars($log['endpoint']); ?></code>
                                    </div>
                                </button>
                            </h2>
                            <div id="flush-collapse-<?php echo $index; ?>" class="accordion-collapse collapse" data-bs-parent="#apiLogsAccordion">
                                <div class="accordion-body px-0 py-2">
                                    <div class="mb-2">
                                        <small class="text-secondary block fw-semibold mb-1">Request Body Payload</small>
                                        <pre class="json-box" style="font-size:0.75rem; padding: 10px; max-height:150px;"><?php 
                                            $reqObj = json_decode($log['request_body'], true);
                                            echo htmlspecialchars($reqObj ? json_encode($reqObj, JSON_PRETTY_PRINT) : $log['request_body']); 
                                        ?></pre>
                                    </div>
                                    <div>
                                        <small class="text-secondary block fw-semibold mb-1">Response JSON</small>
                                        <pre class="json-box" style="font-size:0.75rem; padding: 10px; max-height:150px;"><?php 
                                            $resObj = json_decode($log['response_body'], true);
                                            echo htmlspecialchars($resObj ? json_encode($resObj, JSON_PRETTY_PRINT) : $log['response_body']); 
                                        ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php

} catch (\Exception $e) {
    echo '<div class="alert alert-danger mb-0">Kesalahan query database: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
