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
            body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5; padding: 10px; margin: 0; }
            .invoice-box { max-width: 800px; margin: auto; }
            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .logo { font-size: 26px; font-weight: 800; color: #8B5CF6; letter-spacing: -0.5px; }
            .logo span { color: #1e293b; font-weight: 400; }
            .title { text-align: right; font-size: 24px; font-weight: 300; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
            .divider { height: 3px; background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%); margin-bottom: 30px; }
            .details-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .details-table td { padding: 0 0 10px 0; vertical-align: top; font-size: 13px; }
            .details-title { font-weight: 700; color: #475569; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; margin-bottom: 5px; }
            .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
            .items-table th { background-color: #f8fafc; padding: 12px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
            .items-table td { padding: 16px 10px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
            .total-row td { font-size: 15px; font-weight: 700; color: #1e293b; border-top: 2px solid #e2e8f0; background-color: #f8fafc; padding: 14px 10px; }
            .status-badge { display: inline-block; padding: 4px 12px; background-color: #ecfdf5; color: #047857; font-size: 11px; font-weight: 700; border-radius: 50px; text-transform: uppercase; border: 1px solid #a7f3d0; }
            .footer { margin-top: 60px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 20px; line-height: 1.6; }
        </style>
    </head>
    <body>
        <div class="invoice-box">
            <table class="header-table">
                <tr>
                    <td class="logo">CloudPay <span>AI</span></td>
                    <td class="title">INVOICE</td>
                </tr>
            </table>
            
            <div class="divider"></div>
            
            <table class="details-table">
                <tr>
                    <td style="width: 50%;">
                        <div class="details-title">Diterbitkan Untuk:</div>
                        <strong>' . htmlspecialchars($trx['customer_name']) . '</strong><br>
                        Email: ' . htmlspecialchars($trx['email']) . '<br>
                        No. HP: ' . htmlspecialchars($trx['phone']) . '
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div class="details-title">Detail Transaksi:</div>
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
                        <th style="text-align: right; width: 150px;">Total Tagihan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>' . htmlspecialchars($trx['product_name']) . '</strong><br><span style="font-size:11px; color:#64748b;">Langganan AI Premium Suite</span></td>
                        <td style="text-align: right; font-weight: 600;">Rp ' . number_format($trx['amount'], 0, ',', '.') . '</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Bayar</td>
                        <td style="text-align: right; color: #8B5CF6;">Rp ' . number_format($trx['amount'], 0, ',', '.') . '</td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 30px; font-size: 12px; color: #64748b;">
                <p><strong>Metode Pembayaran:</strong> ' . strtoupper(htmlspecialchars($payment_type ?: 'Sandbox Transfer')) . '</p>
                <p style="font-style: italic; font-size: 11px;">Pembayaran ini diproses secara aman menggunakan sistem Payment Gateway CloudPay AI Sandbox. Ini adalah dokumen transaksi sah simulasi Virtualisasi Cloud.</p>
            </div>
            
            <div class="footer">
                CloudPay AI Premium Hub - Cloud Computing Virtualization &copy; 2026<br>
                Gedung TIK Puskom Kampus, Universitas Lampung.
            </div>
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
        <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #fafafa; padding: 40px 10px; color: #1e293b; line-height: 1.6;">
            <div style="max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                
                <!-- Purple Glowing Header Border -->
                <div style="height: 6px; background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 100%);"></div>
                
                <!-- Brand Header -->
                <div style="padding: 30px 25px 25px 25px; text-align: center; border-bottom: 1px solid #f1f5f9;">
                    <h2 style="color: #8B5CF6; margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.5px;">CloudPay <span style="color:#1e293b; font-weight:400;">AI</span></h2>
                    <p style="color: #64748b; margin: 5px 0 0 0; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Kwitansi Pembayaran Langganan AI</p>
                </div>
                
                <!-- Main Body -->
                <div style="padding: 30px 25px;">
                    <p style="font-size: 15px; margin-top: 0; color: #334155;">Halo <strong>' . htmlspecialchars($trx['customer_name']) . '</strong>,</p>
                    <p style="color: #475569; font-size: 14px; margin-bottom: 25px;">Terima kasih atas transaksi Anda. Berikut adalah rincian pembayaran langganan AI Anda yang telah berhasil diproses oleh secure sandbox.</p>
                    
                    <!-- Details Card -->
                    <div style="background-color: #f8fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px; color: #334155;">
                            <tr>
                                <td style="padding: 8px 0; color: #64748b; font-weight: 500;">Order ID:</td>
                                <td style="padding: 8px 0; font-weight: 700; text-align: right; font-family: monospace; font-size:14px;">' . htmlspecialchars($trx['order_id']) . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; color: #64748b; font-weight: 500;">Produk AI:</td>
                                <td style="padding: 8px 0; font-weight: 700; text-align: right; color: #1e293b;">' . htmlspecialchars($trx['product_name']) . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; color: #64748b; font-weight: 500;">Nominal Bayar:</td>
                                <td style="padding: 8px 0; font-weight: 800; text-align: right; color: #8B5CF6; font-size: 16px;">Rp ' . number_format($trx['amount'], 0, ',', '.') . '</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; color: #64748b; font-weight: 500;">Status Transaksi:</td>
                                <td style="padding: 8px 0; font-weight: 700; text-align: right;"><span style="background-color: ' . ($status == 'settlement' || $status == 'capture' ? '#ecfdf5' : '#fef2f2') . '; color: ' . $status_color . '; padding: 3px 10px; border-radius: 50px; font-size: 11px; border: 1px solid ' . ($status == 'settlement' || $status == 'capture' ? '#a7f3d0' : '#fecaca') . ';">' . $status_label . '</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; color: #64748b; font-weight: 500;">Waktu Pembayaran:</td>
                                <td style="padding: 8px 0; font-weight: 700; text-align: right;">' . date('d F Y H:i') . ' WIB</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Action Info -->
                    <div style="text-align: center; margin-bottom: 25px;">
                        <p style="font-size: 13px; color: #64748b; margin: 0;">Berkas invoice PDF resmi telah dilampirkan langsung pada email ini.</p>
                    </div>
                    
                    <!-- Divider -->
                    <div style="border-top: 1px solid #f1f5f9; padding-top: 20px; margin-top: 25px;">
                        <p style="color: #94a3b8; font-size: 11px; line-height: 1.6; text-align: center; margin: 0;">
                            Email ini dikirim secara otomatis oleh simulator sistem tugas besar Virtualisasi Cloud Computing. Jika Anda memerlukan bantuan presentasi, silakan hubungi tim pengembang Anda.
                        </p>
                    </div>
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
