<?php
define('IS_ADMIN_DIR', true);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'pdf' && isset($_GET['order_id'])) {
    // 1. Export Single Invoice PDF
    $order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_SPECIAL_CHARS);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $trx = $stmt->fetch();
        
        if (!$trx) {
            die("Transaksi tidak ditemukan.");
        }
        
        // Ownership validation: normal users can only download their own invoice
        if ($role !== 'admin' && (int)$trx['user_id'] !== (int)$user_id) {
            http_response_code(403);
            die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;background:#fce8e6;color:#a82c2c;border-radius:8px;border:1px solid #f5c2c2;'>
                    <h2 style='margin-top:0;'>403 Forbidden - Akses Ditolak</h2>
                    <p>Anda hanya diperbolehkan mengunduh invoice milik Anda sendiri.</p>
                    <hr style='border-color:#f5c2c2;'>
                    <a href='../dashboard.php' style='color:#a82c2c;text-decoration:none;font-weight:bold;'>&larr; Kembali ke Dashboard</a>
                 </div>");
        }
        
        // Write audit log
        $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address) VALUES (?, 'Export PDF', ?, ?)");
        $stmtAudit->execute([$username, "Exported PDF invoice for $order_id", $_SERVER['REMOTE_ADDR']]);
        
        // Check if PDF already exists
        $filename = 'INV-' . date('Ymd', strtotime($trx['created_at'])) . '-' . substr($order_id, -6) . '.pdf';
        $filePath = __DIR__ . '/../uploads/invoices/' . $filename;
        
        if (file_exists($filePath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            // Re-generate if class exists
            if (class_exists('Dompdf\Dompdf')) {
                // Generates PDF dynamically (reusing notification.php logic)
                require_once __DIR__ . '/../notification.php';
                $newPath = generateInvoicePDF($trx, $trx['transaction_status'], $trx['payment_type']);
                $absoluteNewPath = __DIR__ . '/../' . $newPath;
                if (file_exists($absoluteNewPath)) {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    readfile($absoluteNewPath);
                    exit;
                }
            } else {
                // If Dompdf not loaded, stream the txt fallback invoice
                $txtFilename = 'INV-' . date('Ymd', strtotime($trx['created_at'])) . '-' . substr($order_id, -6) . '.txt';
                $txtPath = __DIR__ . '/../uploads/invoices/' . $txtFilename;
                if (file_exists($txtPath)) {
                    header('Content-Type: text/plain');
                    header('Content-Disposition: attachment; filename="' . $txtFilename . '"');
                    readfile($txtPath);
                    exit;
                } else {
                    die("Dokumen Invoice PDF belum dibuat oleh system webhook. Hubungkan Midtrans Sandbox atau gunakan simulator pembayaran.");
                }
            }
        }
    } catch (\Exception $e) {
        die("Error exporting PDF: " . $e->getMessage());
    }
}

// 2. Export Filtered Transactions (PDF or Excel) - Restricted to admin only
if ($role !== 'admin') {
    http_response_code(403);
    die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;background:#fce8e6;color:#a82c2c;border-radius:8px;border:1px solid #f5c2c2;'>
            <h2 style='margin-top:0;'>403 Forbidden - Akses Ditolak</h2>
            <p>Akses ditolak: Hanya administrator yang dapat mengekspor laporan transaksi global.</p>
            <hr style='border-color:#f5c2c2;'>
            <a href='../dashboard.php' style='color:#a82c2c;text-decoration:none;font-weight:bold;'>&larr; Kembali ke Dashboard</a>
         </div>");
}
$search = isset($_GET['search']) ? trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS)) : '';
$status_filter = isset($_GET['status']) ? trim(filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS)) : '';

$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(order_id LIKE ? OR customer_name LIKE ? OR email LIKE ? OR product_name LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}

if ($status_filter !== '') {
    if ($status_filter == 'success') {
        $where_clauses[] = "transaction_status IN ('settlement', 'capture', 'success')";
    } else if ($status_filter == 'failed') {
        $where_clauses[] = "transaction_status IN ('expire', 'deny', 'cancel', 'failed')";
    } else {
        $where_clauses[] = "transaction_status = ?";
        $params[] = $status_filter;
    }
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

try {
    $stmtTrx = $pdo->prepare("SELECT * FROM transactions $where_sql ORDER BY created_at DESC");
    $stmtTrx->execute($params);
    $transactions = $stmtTrx->fetchAll();
} catch (\Exception $e) {
    die("Database query error: " . $e->getMessage());
}

if ($action === 'pdf') {
    // Export List to PDF
    if (!class_exists('Dompdf\Dompdf')) {
        die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;background:#fff3cd;color:#856404;border-radius:8px;border:1px solid #ffeeba;'>
                <h3>Dependensi Dompdf Belum Terinstal</h3>
                <p>Silakan jalankan perintah <code>composer install</code> di folder root project untuk menginstal pustaka Dompdf.</p>
             </div>");
    }
    
    // Log audit
    try {
        $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address) VALUES (?, 'Export PDF', 'Exported transactions list to PDF', ?)");
        $stmtAudit->execute([$username, $_SERVER['REMOTE_ADDR']]);
    } catch (\Exception $e) {}

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Laporan Transaksi Sandbox</title>
        <style>
            body { font-family: sans-serif; font-size: 11px; color: #333; }
            h2 { text-align: center; color: #6366F1; margin-bottom: 5px; }
            p.sub { text-align: center; margin-top: 0; color: #666; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background-color: #6366F1; color: white; padding: 8px; text-align: left; }
            td { padding: 8px; border-bottom: 1px solid #E5E7EB; }
            .status { font-weight: bold; text-transform: uppercase; }
            .footer { margin-top: 30px; text-align: right; color: #9CA3AF; font-size: 9px; }
        </style>
    </head>
    <body>
        <h2>LAPORAN TRANSAKSI SANDBOX MIDTRANS</h2>
        <p class="sub">Diunduh pada: ' . date('d/m/Y H:i') . ' WIB | Oleh: ' . htmlspecialchars($username) . '</p>
        
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Produk / Layanan</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>';
            
            foreach ($transactions as $tx) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($tx['order_id']) . '</td>
                    <td>' . htmlspecialchars($tx['customer_name']) . '</td>
                    <td>' . htmlspecialchars($tx['email']) . '</td>
                    <td>' . htmlspecialchars($tx['product_name']) . '</td>
                    <td>Rp ' . number_format($tx['amount'], 0, ',', '.') . '</td>
                    <td class="status">' . htmlspecialchars($tx['transaction_status']) . '</td>
                    <td>' . date('d/m/y H:i', strtotime($tx['created_at'])) . '</td>
                </tr>';
            }
            
    $html .= '</tbody>
        </table>
        
        <div class="footer">
            CloudPay Sandbox Simulator - Cloud Computing Virtualization Assignment &copy; 2026
        </div>
    </body>
    </html>';

    try {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $dompdf->stream("Laporan-Transaksi-" . date('Ymd') . ".pdf", ["Attachment" => true]);
        exit;
    } catch (\Exception $e) {
        die("Gagal memproses PDF: " . $e->getMessage());
    }

} else if ($action === 'excel') {
    // Export List to Excel
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;background:#fff3cd;color:#856404;border-radius:8px;border:1px solid #ffeeba;'>
                <h3>Dependensi PhpSpreadsheet Belum Terinstal</h3>
                <p>Silakan jalankan perintah <code>composer install</code> di folder root project untuk menginstal pustaka PhpSpreadsheet.</p>
             </div>");
    }
    
    // Log audit
    try {
        $stmtAudit = $pdo->prepare("INSERT INTO audit_logs (username, action, details, ip_address) VALUES (?, 'Export Excel', 'Exported transactions list to Excel', ?)");
        $stmtAudit->execute([$username, $_SERVER['REMOTE_ADDR']]);
    } catch (\Exception $e) {}

    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header Titles
        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', 'ORDER ID');
        $sheet->setCellValue('C1', 'NAMA PELANGGAN');
        $sheet->setCellValue('D1', 'EMAIL');
        $sheet->setCellValue('E1', 'TELEPON');
        $sheet->setCellValue('F1', 'PRODUK / LAYANAN');
        $sheet->setCellValue('G1', 'NOMINAL AMOUNT');
        $sheet->setCellValue('H1', 'METODE PEMBAYARAN');
        $sheet->setCellValue('I1', 'STATUS');
        $sheet->setCellValue('J1', 'WAKTU TRANSAKSI');
        
        // Styling headers
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        
        $row_num = 2;
        foreach ($transactions as $index => $tx) {
            $sheet->setCellValue('A' . $row_num, $index + 1);
            $sheet->setCellValue('B' . $row_num, $tx['order_id']);
            $sheet->setCellValue('C' . $row_num, $tx['customer_name']);
            $sheet->setCellValue('D' . $row_num, $tx['email']);
            $sheet->setCellValue('E' . $row_num, "'" . $tx['phone']); // Single quote to enforce string formatting
            $sheet->setCellValue('F' . $row_num, $tx['product_name']);
            $sheet->setCellValue('G' . $row_num, $tx['amount']);
            $sheet->setCellValue('H' . $row_num, strtoupper($tx['payment_type'] ?: 'N/A'));
            $sheet->setCellValue('I' . $row_num, strtoupper($tx['transaction_status']));
            $sheet->setCellValue('J' . $row_num, $tx['created_at']);
            $row_num++;
        }
        
        // Auto fit columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan-Transaksi-' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
        
    } catch (\Exception $e) {
        die("Gagal memproses Excel: " . $e->getMessage());
    }
} else {
    header("Location: transactions.php");
    exit;
}
