<?php
// Script tes SMTP port 465 SSL di cPanel
error_reporting(E_ALL);
ini_set('display_errors', 1);

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (preg_match('/^"#(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            }
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    die("<h3>Error: PHPMailer tidak ditemukan. Pastikan folder vendor sudah terupload.</h3>");
}

$mail = new \PHPMailer\PHPMailer\PHPMailer(true);

echo "<h2>🔧 Diagnostik SMTP Gmail Port 465 (SSL)</h2>";
echo "<pre>";

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'sotoaiamenak@gmail.com';
    $mail->Password   = 'wcyqlqsqlomfqayf'; // Gmail App Password
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;
    
    // Disable SSL verification for local environment compatibility
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom('sotoaiamenak@gmail.com', 'CloudPay VCC');
    $mail->addAddress('anazkyp@gmail.com', 'Anazky Putra');
    $mail->Subject = 'Tes Koneksi SMTP Port 465 SSL';
    $mail->isHTML(true);
    $mail->Body = '<h3>Koneksi Port 465 SSL Berhasil!</h3><p>Email ini dikirim menggunakan Gmail SMTP port 465 dari cPanel.</p>';

    // Enable connection debug
    $mail->SMTPDebug = 3;
    $mail->Debugoutput = function($str, $level) {
        echo htmlspecialchars($str) . "\n";
    };

    if ($mail->send()) {
        echo "\n<b>✅ KONEKSI & PENGIRIMAN BERHASIL! Silakan cek email anazkyp@gmail.com</b>\n";
    } else {
        echo "\n<b>❌ Gagal mengirim email.</b>\n";
    }

} catch (Exception $e) {
    echo "\n<b>❌ EXCEPTION:</b> " . htmlspecialchars($e->getMessage()) . "\n";
}

echo "</pre>";
