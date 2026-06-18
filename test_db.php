<?php
// Script untuk test koneksi database
// Berguna untuk debug saat deploy ke cPanel

// Aktifkan error reporting untuk melihat detail error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Konfigurasi Database (bisa diubah sesuai kebutuhan atau dicocokkan dengan .env)
$host = '100.102.176.5';
$port = '3306';
$dbname = 'vcc_payment_db';
$user = 'vcc_db_user';
$pass = 'Secret123!';

echo "<h2>🔧 Database Connection Test</h2>";
echo "<p>Mencoba terhubung ke <b>$host:$port</b>...</p>";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5 // Timeout 5 detik
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "<div style='color: green; padding: 15px; border: 1px solid green; border-radius: 5px;'>
            <h3>✅ Koneksi Berhasil!</h3>
            <p>Berhasil terhubung ke database <b>$dbname</b> di host <b>$host</b>.</p>
          </div>";

} catch (PDOException $e) {
    echo "<div style='color: red; padding: 15px; border: 1px solid red; border-radius: 5px;'>
            <h3>❌ Koneksi Gagal!</h3>
            <p><b>Error Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><b>Error Code:</b> " . htmlspecialchars($e->getCode()) . "</p>
          </div>";
    
    echo "<h4>Tips Debugging:</h4>";
    echo "<ul>
            <li>Pastikan firewall / IPTables di VM Ubuntu (Tailscale) mengizinkan port 3306.</li>
            <li>Pastikan MySQL di VM mengizinkan 'bind-address' ke IP `100.102.176.5` atau `0.0.0.0` (buka file /etc/mysql/mysql.conf.d/mysqld.cnf).</li>
            <li>Pastikan di command MySQL sudah menjalankan perintah GRANT dengan benar.</li>
            <li>Pastikan cPanel dosen tidak memblokir koneksi keluar ke IP 100.x.x.x lewat firewall-nya.</li>
          </ul>";
}
