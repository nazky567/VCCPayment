<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mail.php';

/**
 * Generate Invoice PDF using Dompdf
 */
function generateInvoicePDF($trx, $status, $payment_type) {
    $order_id = $trx['order_id'];
    $invoice_no = 'INV-' . date('Ymd') . '-' . substr($order_id, -6);
    
    $uploadDir = __DIR__ . '/../uploads/invoices';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filename = $invoice_no . '.pdf';
    $filePath = 'uploads/invoices/' . $filename;
    $absolutePath = $uploadDir . '/' . $filename;
    
    // Simple HTML Invoice template for DOMPDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Invoice - ' . $invoice_no . '</title>
        <style>
            body { font-family: sans-serif; color: #333; line-height: 1.4; padding: 20px; }
            .header { border-bottom: 2px solid #6366F1; padding-bottom: 20px; margin-bottom: 20px; }
            .logo { font-size: 24px; font-weight: bold; color: #6366F1; }
            .title { text-align: right; font-size: 20px; color: #555; }
            .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            .details-table td { padding: 8px 0; vertical-align: top; }
            .items-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
            .items-table th { background-color: #F3F4F6; padding: 10px; text-align: left; font-size: 14px; border-bottom: 1px solid #E5E7EB; }
            .items-table td { padding: 12px 10px; border-bottom: 1px solid #E5E7EB; font-size: 14px; }
            .total-row { font-size: 16px; font-weight: bold; background-color: #EEF2FF; }
            .status-badge { display: inline-block; padding: 4px 10px; background-color: #DEF7EC; color: #03543F; font-size: 12px; font-weight: bold; border-radius: 4px; }
            .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 20px; }
        </style>
    </head>
    <body>
        <table style="width:100%">
            <tr>
                <td class="logo">CloudPay Sandbox</td>
                <td class="title">INVOICE</td>
            </tr>
        </table>
        
        <div class="header"></div>
        
        <table class="details-table">
            <tr>
                <td style="width:50%">
                    <strong>Diterbitkan Untuk:</strong><br>
                    ' . htmlspecialchars($trx['customer_name']) . '<br>
                    Email: ' . htmlspecialchars($trx['email']) . '<br>
                    HP: ' . htmlspecialchars($trx['phone']) . '
                </td>
                <td style="width:50%; text-align:right;">
                    <strong>Nomor Invoice:</strong> ' . $invoice_no . '<br>
                    <strong>Order ID:</strong> ' . htmlspecialchars($trx['order_id']) . '<br>
                    <strong>Tanggal:</strong> ' . date('d F Y') . '<br>
                    <strong>Status:</strong> <span class="status-badge">SUKSES</span>
                </td>
            </tr>
        </table>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi Layanan / Produk</th>
                    <th style="text-align:right;">Harga (IDR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>' . htmlspecialchars($trx['product_name']) . '</td>
                    <td style="text-align:right;">Rp ' . number_format($trx['amount'], 0, ',', '.') . '</td>
                </tr>
                <tr class="total-row">
                    <td>Total Bayar</td>
                    <td style="text-align:right;">Rp ' . number_format($trx['amount'], 0, ',', '.') . '</td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top:20px; font-size:12px;">
            <p><strong>Metode Pembayaran:</strong> ' . strtoupper(htmlspecialchars($payment_type ?: 'Sandbox Transfer')) . '</p>
            <p><em>Pembayaran ini diproses secara aman menggunakan sistem Payment Gateway Sandbox. Ini adalah dokumen transaksi sah untuk demo Virtualisasi Cloud.</em></p>
        </div>
        
        <div class="footer">
            CloudPay Sandbox Simulator - Cloud Computing Virtualization &copy; 2026
        </div>
    </body>
    </html>';

    if (class_exists('Dompdf\Dompdf')) {
        try {
            $dompdf = new \Dompdf\Dompdf([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true
            ]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Save to absolute path
            file_put_contents($absolutePath, $dompdf->output());
            return $filePath;
        } catch (\Exception $e) {
            error_log("Dompdf error: " . $e->getMessage());
        }
    }
    
    // Fallback: Create structured text invoice if Dompdf is not installed yet
    $textInvoice = "=== INVOICE ===\n";
    $textInvoice .= "Invoice No: $invoice_no\n";
    $textInvoice .= "Order ID: $order_id\n";
    $textInvoice .= "Customer: " . $trx['customer_name'] . "\n";
    $textInvoice .= "Product: " . $trx['product_name'] . "\n";
    $textInvoice .= "Amount: Rp " . number_format($trx['amount'], 0, ',', '.') . "\n";
    $textInvoice .= "Status: SUCCESS\n";
    file_put_contents($uploadDir . '/' . $invoice_no . '.txt', $textInvoice);
    return 'uploads/invoices/' . $invoice_no . '.txt';
}

/**
 * Send email notification
 */
function sendNotificationEmail($trx, $status, $pdfPath) {
    try {
        $mail = getPHPMailerInstance();
        
        $mail->addAddress($trx['email'], $trx['customer_name']);
        $mail->isHTML(true);
        
        // Subject and Status styling
        $subject = 'Pembayaran Baru Terbuat - Pending';
        $status_label = 'PENDING';
        $status_color = '#F59E0B';
        
        if ($status == 'settlement' || $status == 'capture') {
            $subject = 'Pembayaran Berhasil - Terima Kasih';
            $status_label = 'SUCCESS';
            $status_color = '#10B981';
            
            // Attach Invoice if file exists
            if (!empty($pdfPath) && file_exists(__DIR__ . '/../' . $pdfPath)) {
                $mail->addAttachment(__DIR__ . '/../' . $pdfPath, basename($pdfPath));
            }
        } else if ($status == 'deny' || $status == 'cancel' || $status == 'expire') {
            $subject = 'Pembayaran Gagal / Dibatalkan';
            $status_label = 'FAILED';
            $status_color = '#EF4444';
        }
        
        $mail->Subject = $subject;
        
        // HTML Receipt Style Template
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; background-color: #f4f5f7; padding: 30px; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.08); border-top: 6px solid #6366F1;">
                <div style="padding: 25px; border-bottom: 1px solid #f0f0f0; text-align: center;">
                    <h2 style="color: #6366F1; margin: 0; font-size: 24px;">CloudPay Sandbox</h2>
                    <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">Simulasi Pembayaran Virtualisasi Cloud</p>
                </div>
                <div style="padding: 30px;">
                    <p style="font-size: 16px; margin-top: 0;">Halo, <strong>' . htmlspecialchars($trx['customer_name']) . '</strong></p>
                    <p style="color: #555; line-height: 1.5;">Notifikasi transaksi Anda dari sistem payment gateway sandbox.</p>
                    
                    <div style="background-color: #fafbfc; border: 1px solid #eaeaea; border-radius: 6px; padding: 20px; margin: 25px 0;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <tr>
                                <td style="padding: 6px 0; color: #666;">Order ID:</td>
                                <td style="padding: 6px 0; font-weight: bold; text-align: right;">' . htmlspecialchars($trx['order_id']) . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: #666;">Produk:</td>
                                <td style="padding: 6px 0; font-weight: bold; text-align: right;">' . htmlspecialchars($trx['product_name']) . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: #666;">Nominal:</td>
                                <td style="padding: 6px 0; font-weight: bold; text-align: right; color: #6366F1; font-size: 16px;">Rp ' . number_format($trx['amount'], 0, ',', '.') . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: #666;">Status:</td>
                                <td style="padding: 6px 0; font-weight: bold; text-align: right; color: ' . $status_color . ';">' . $status_label . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: #666;">Tanggal:</td>
                                <td style="padding: 6px 0; font-weight: bold; text-align: right;">' . date('d F Y H:i') . ' WIB</td>
                            </tr>
                        </table>
                    </div>
                    
                    <p style="color: #666; font-size: 13px; line-height: 1.5; text-align: center; border-top: 1px solid #f0f0f0; padding-top: 20px; margin-top: 30px;">
                        Email ini dikirim secara otomatis oleh simulator payment gateway tugas kuliah Virtualisasi Cloud Computing. Lampiran invoice PDF akan disertakan apabila pembayaran berhasil (Success).
                    </p>
                </div>
            </div>
        </div>';
        
        $mail->AltBody = "Notifikasi Transaksi:\nOrder ID: " . $trx['order_id'] . "\nProduk: " . $trx['product_name'] . "\nNominal: Rp " . number_format($trx['amount'], 0, ',', '.') . "\nStatus: " . $status_label;
        
        $mail->send();
        
        // Log Timeline Event
        $pdo = $GLOBALS['pdo'];
        $stmtEvent = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data) VALUES (?, 'Email Notification Sent', ?)");
        $stmtEvent->execute([$trx['order_id'], json_encode(['email' => $trx['email'], 'status' => $status_label])]);
        
    } catch (\Exception $e) {
        error_log("Email sending failure: " . $e->getMessage());
        $pdo = $GLOBALS['pdo'];
        $stmtEvent = $pdo->prepare("INSERT INTO payment_events (order_id, event_name, event_data) VALUES (?, 'Email Notification Failed', ?)");
        $stmtEvent->execute([$trx['order_id'], json_encode(['error' => $e->getMessage()])]);
    }
}
