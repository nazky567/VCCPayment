# Midtrans Sandbox Payment Gateway Simulator

Proyek simulasi payment gateway ini dikembangkan khusus untuk tugas mata kuliah **Virtualisasi Cloud Computing**. Aplikasi ini mensimulasikan alur pembayaran e-commerce dari checkout, pembuatan snap token API, callback webhook, log aktivitas cloud, status health-check, hingga pengiriman invoice via SMTP email menggunakan PHP Native dan Bootstrap 5.

---

## Fitur Utama
1. **Checkout & Snap Integration**: Form input pelanggan yang dinamis terintegrasi ke SDK Snap Midtrans Sandbox.
2. **Local Simulation Mode**: Apabila kunci API belum dikonfigurasi, sistem secara otomatis beralih ke mode simulasi offline yang memungkinkan pengujian (Sukses, Pending, Gagal) 100% lokal tanpa dependensi internet/webhook.
3. **Database Integration**: Menyimpan status transaksi, timeline pembayaran (`payment_events`), request/response JSON payload (`api_logs`), dan log audit (`audit_logs`).
4. **Admin Dashboard & Live Monitor**: Menampilkan total statistik grafik Chart.js, audit trail administrator, dan callback monitor realtime (refresh otomatis 10 detik).
5. **PDF & Excel Export**: Mengunduh invoice PDF via Dompdf dan data laporan spreadsheet via PhpSpreadsheet.
6. **Hosting Health Checker**: Memantau kecocokan versi PHP server hosting, koneksi DB, latency Midtrans, sisa space disk storage cPanel, dan RAM virtual.

---

## Persyaratan Sistem
* PHP >= 7.4 (Rekomendasi PHP 8.x)
* Database MySQL / MariaDB
* Composer (untuk manajemen dependensi)
* Ekstensi PHP aktif: `openssl`, `curl`, `pdo_mysql`

---

## Struktur Direktori
```text
payment-gateway-sandbox/
├── admin/                  # Dashboard manajemen & data transaksi
│   ├── dashboard.php
│   ├── transactions.php
│   ├── callback-monitor.php
│   ├── system-info.php
│   └── export.php
├── config/                 # Konfigurasi database, midtrans, & SMTP
│   ├── database.php
│   ├── midtrans.php
│   └── mail.php
├── assets/                 # CSS/JS frontend layouting
├── uploads/                # Direktori penyimpanan PDF invoice
├── tools/                  # Halaman verifikasi konektivitas API developer
├── .env                    # Variabel konfigurasi lingkungan
├── database.sql            # Skema sql migrasi database
├── seed.php                # Seeding 50 dummy records untuk demonstrasi
└── README.md
```

---

## Petunjuk Instalasi

### A. Localhost (XAMPP / Laragon)
1. Clone atau salin folder proyek ke direktori `htdocs` (XAMPP) atau `www` (Laragon).
2. Buat database baru bernama `payment_sandbox` di phpMyAdmin, lalu import berkas `database.sql`.
3. Jalankan perintah instalasi pustaka dependensi via terminal pada folder proyek:
   ```bash
   composer install
   ```
4. Ubah nama berkas `.env.example` menjadi `.env`, lalu edit nilainya:
   - Sesuaikan konfigurasi database (`DB_USER`, `DB_PASS`, dll).
   - Masukkan `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` Sandbox Anda.
   - Masukkan konfigurasi SMTP email (bisa memakai Mailtrap untuk pengujian aman).
5. Buka browser dan akses: `http://localhost/payment-gateway-sandbox/index.php`.
6. Akses halaman `http://localhost/payment-gateway-sandbox/seed.php` sekali untuk mempopulasikan data dummy.

---

### B. Deployment ke cPanel Hosting
1. Kompres seluruh berkas proyek ke dalam format `.zip` (abaikan folder `vendor` karena lebih baik diinstal langsung).
2. Masuk ke cPanel, buka **File Manager**, dan unggah berkas zip ke folder `public_html` atau direktori subdomain Anda. Ekstrak berkas tersebut.
3. Buka menu **MySQL Database Wizard** di cPanel:
   - Buat database baru (misalnya `username_pay_sandbox`).
   - Buat user database baru dan hubungkan ke database dengan memberikan hak akses penuh (*All Privileges*).
   - Buka **phpMyAdmin** cPanel, pilih database tersebut, dan import berkas `database.sql`.
4. Edit berkas `.env` langsung menggunakan file editor cPanel untuk menyesuaikan nama database, user, dan password baru yang telah dibuat.
5. Jalankan `composer install` pada server hosting:
   - Jika memiliki akses SSH: hubungkan ke SSH cPanel lalu jalankan `composer install` di direktori proyek.
   - Jika tidak memiliki akses SSH: jalankan `composer install` secara lokal terlebih dahulu lalu unggah folder `vendor` yang dihasilkan ke server hosting.
6. Pada Midtrans Dashboard Sandbox, daftarkan URL callback Webhook Anda ke menu **Settings > Access Keys**:
   ```text
   http://domainanda.com/notification.php
   ```

---

## Detail Login Default Admin
Akses halaman admin pada `/admin/login.php`:
* **Administrator**: Username: `admin` | Password: `admin123` *(Akses Penuh)*
* **Operator**: Username: `operator` | Password: `operator123` *(Lihat & Ekspor Transaksi)*
* **Viewer**: Username: `viewer` | Password: `viewer123` *(Read-only)*

---

## Panduan Pengujian API & SMTP
Gunakan halaman pengetesan developer pada:
```text
http://domainanda.com/tools/api-test.php
```
Di halaman ini Anda dapat:
* Memverifikasi apakah database MySQL terhubung.
* Memverifikasi apakah server hosting dapat mengirim email dengan PHPMailer (masukkan alamat email Anda dan klik 'Kirim Uji Coba').
* Memverifikasi latensi koneksi keluar ke API Sandbox Midtrans.
