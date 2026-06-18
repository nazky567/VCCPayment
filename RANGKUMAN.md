# Rangkuman Sistem: CloudPay Sandbox Simulator
## Tugas Akhir Mata Kuliah: Virtualisasi Cloud Computing

---

### 1. Deskripsi Proyek
Proyek ini adalah **Sistem Simulasi Payment Gateway Terintegrasi Midtrans Sandbox** yang dikembangkan menggunakan PHP Native dan Bootstrap 5. Sistem ini dirancang untuk mensimulasikan alur transaksi e-commerce, mulai dari pengisian data pelanggan, integrasi API pembayaran (*Payment Gateway*), pencatatan timeline status pembayaran (*payment events*), pengiriman invoice otomatis melalui email (SMTP/PHP Mail), hingga dasbor monitoring administrator secara *realtime*.

---

### 2. Fitur Utama Sistem

1. **Portal Checkout Pelanggan (`checkout.php`)**
   * Menginput data diri pelanggan secara dinamis (Nama, Email, No. HP) dan memilih produk layanan cloud.
   * Dilengkapi sistem proteksi keamanan CSRF (*Cross-Site Request Forgery*) untuk mencegah manipulasi form.

2. **Integrasi Midtrans Snap API & Offline Simulator**
   * **Mode Online (Midtrans Sandbox):** Menampilkan modal popup SNAP Midtrans secara dinamis untuk metode pembayaran Virtual Account simulator dan QRIS sandbox.
   * **Mode Offline (Local Simulation Mode):** Jika server hosting kehilangan koneksi internet atau kunci API belum dipasang di `.env`, sistem otomatis beralih ke panel simulator offline lokal sehingga demonstrasi pembayaran (Sukses, Pending, Gagal) tetap dapat berjalan 100%.

3. **Dasbor Admin Realtime (`/admin/dashboard.php`)**
   * **Visualisasi Metrik:** Menampilkan total transaksi, pendapatan, presentasi status transaksi, serta diagram grafik tren menggunakan Chart.js.
   * **Aktivitas Log Audit:** Mencatat setiap tindakan admin untuk keamanan monitoring.
   * **Pencarian & Pagination:** Halaman pengelolaan transaksi yang dilengkapi fitur pencarian data dan pembagian halaman.

4. **Live Monitor Callback Webhook (`/admin/callback-monitor.php`)**
   * Halaman khusus untuk memantau request callback asinkronus (*webhook notification*) dari Midtrans secara realtime yang memuat ulang data otomatis via AJAX setiap 10 detik.

5. **Server & Hosting Health Checker (`/admin/system-info.php`)**
   * Menampilkan spesifikasi sistem operasi server, versi PHP, penggunaan RAM virtual, sisa kapasitas penyimpanan disk hosting cPanel, serta latensi jaringan keluar.

6. **Notifikasi Email Otomatis & Lampiran PDF**
   * Mengirimkan notifikasi status pembayaran langsung ke email pelanggan (menggunakan PHPMailer).
   * Melampirkan berkas invoice PDF resmi yang digenerate secara otomatis menggunakan library **Dompdf** ketika pembayaran sukses terkonfirmasi.

7. **Fitur Ekspor Dokumen (`/admin/export.php`)**
   * Memungkinkan admin mengekspor data laporan transaksi global ke format Excel (.xlsx) atau mengunduh invoice individu dalam format PDF.

---

### 3. Arsitektur Komponen & Teknologi

* **Backend / Logika:** PHP Native (Rekomendasi PHP 8.x)
* **Frontend / Desain:** HTML5, CSS3 (Custom Dark Theme), Bootstrap 5, Bootstrap Icons, Chart.js
* **Database:** MySQL / MariaDB (PDO Prepared Statements untuk pencegahan SQL Injection)
* **Manajemen Pustaka:** Composer (`vlucas/phpdotenv` untuk env, `dompdf/dompdf` untuk PDF, `phpmailer/phpmailer` untuk email)
* **Layanan Cloud Eksternal:**
  * **Midtrans Sandbox:** Layanan Simulasi Payment Gateway API.
  * **SMTP Relay / PHP Mail:** Layanan pengiriman email otomatis.

---

### 4. Struktur Direktori Proyek

```text
VCCPayment/
├── admin/                  # Dasbor manajemen & monitoring administrator
│   ├── dashboard.php       # Ringkasan statistik & diagram transaksi
│   ├── transactions.php    # Kelola & filter data transaksi
│   ├── callback-monitor.php# Monitoring live webhook (refresh 10s)
│   ├── system-info.php     # Diagnostik RAM/Disk/Network server
│   └── export.php          # Ekspor data Excel & PDF
├── config/                 # Folder konfigurasi inti aplikasi
│   ├── database.php        # Inisialisasi koneksi DB (PDO) & dotenv
│   ├── midtrans.php        # Inisiasi setting client/server key Midtrans
│   ├── mail.php            # Driver PHPMailer (SMTP / PHP Mail)
│   └── payment_helpers.php # Helper pembuatan PDF invoice & pengiriman email
├── assets/                 # Berkas CSS kustom, gambar, dan JavaScript
├── uploads/                # Tempat penyimpanan file invoice PDF di server
├── tools/                  # Halaman developer untuk tes SMTP & konektivitas API
│   └── api-test.php        # Tools diagnostik mandiri developer
├── .env                    # Variabel konfigurasi kredensial (Sensitif)
├── database.sql            # Skema SQL migrasi database awal
├── seed.php                # Seeder otomatis untuk memasukkan 50 transaksi dummy
└── RANGKUMAN.md            # Berkas dokumentasi rangkuman ini
```

---

### 5. Detail Login Default Akun Presentasi

1. **Dashboard Administrator (`/admin/login.php`)**
   * **Username:** `admin`
   * **Password:** `admin123`

2. **Portal Pengguna (`/login.php`)**
   * **Username:** `user`
   * **Password:** `user123`
