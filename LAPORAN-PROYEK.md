# LAPORAN PROYEK MATA KULIAH VIRTUALISASI CLOUD COMPUTING
## Sistem Simulasi Payment Gateway Terintegrasi Midtrans Sandbox

---

### BAB 1: LATAR BELAKANG

#### 1.1 Konteks Proyek
Dalam perkembangan infrastruktur modern, komputasi awan (*cloud computing*) telah mengubah cara aplikasi web dikembangkan, di-deploy, dan dioperasikan. Salah satu implementasi paling krusial dari layanan komputasi awan adalah integrasi sistem pihak ketiga melalui API (*Application Programming Interface*). Penanganan transaksi pembayaran merupakan tulang punggung industri e-commerce saat ini. Namun, mengintegrasikan gerbang pembayaran (*payment gateway*) memerlukan pemahaman yang matang mengenai komunikasi antar server (*server-to-server*), penanganan *event* asinkronus (*webhook*), keamanan data, dan pemantauan sistem secara *realtime*.

#### 1.2 Tujuan Proyek
Proyek ini dikembangkan sebagai aplikasi demonstrasi terpadu untuk mata kuliah **Virtualisasi Cloud Computing** dengan tujuan:
* Mensimulasikan integrasi API e-payment menggunakan **Midtrans Sandbox** secara aman.
* Menerapkan konsep *Client-Server Architecture* dan *Database-as-a-Service (DBaaS)* dalam menyimpan data transaksi.
* Mengintegrasikan layanan *Cloud Email SMTP* melalui PHPMailer untuk memberikan notifikasi otomatis ke pengguna.
* Menyediakan dasbor monitoring *realtime* untuk melacak event API webhook, log audit administrator, dan status *health check* hosting.
* Membuktikan kesiapan aplikasi untuk di-deploy ke virtualisasi hosting cPanel.

---

### BAB 2: ANALISIS SISTEM

#### 2.1 Analisis Kebutuhan Fungsional
Aplikasi harus dapat memenuhi kebutuhan sebagai berikut:
1. **Sisi Pelanggan**:
   * Melakukan checkout dengan mengisi nama, email, nomor telepon, dan memilih produk/layanan cloud.
   * Menampilkan popup pembayaran Snap Midtrans secara aman.
   * Mengalihkan pelanggan secara otomatis ke halaman sukses, pending, atau gagal berdasarkan status akhir pembayaran.
   * Mengirim invoice PDF resmi ke email pelanggan setelah pembayaran berhasil diproses.
2. **Sisi Administrator**:
   * Menyediakan portal masuk aman dengan pembagian peran (*admin*, *operator*, *viewer*).
   * Menampilkan dasbor visual metrik total transaksi, status pembayaran, pendapatan harian/bulanan, dan grafik tren.
   * Menyediakan menu pencarian, penyaringan, dan penomoran halaman (*pagination*) data transaksi.
   * Menyediakan menu peninjauan log payload API secara realtime dengan interval 10 detik.
   * Menyediakan menu pemantauan kesehatan hosting (sisa disk storage, RAM virtual, konektivitas internet).
   * Mengekspor rekaman data transaksi ke dalam format Excel (.xlsx) atau PDF.

#### 2.2 Analisis Kebutuhan Non-Fungsional
* **Keamanan**: Melindungi data input dari serangan XSS (*Cross-Site Scripting*) dan SQL Injection menggunakan PDO Prepared Statements. Melindungi form checkout dari pembajakan CSRF (*Cross-Site Request Forgery*).
* **Portabilitas**: Menggunakan konfigurasi dinamis berbasis berkas `.env` sehingga kode program tidak diubah ketika dipindahkan dari lingkungan lokal (*localhost*) ke hosting produksi cPanel.
* **Resiliensi**: Menyediakan mode simulasi *offline* untuk demo lokal apabila server hosting kehilangan koneksi internet atau kunci API Midtrans belum dikonfigurasi.

---

### BAB 3: PERANCANGAN SISTEM

#### 3.1 Aliran Komunikasi Komponen
Sistem dirancang dengan memanfaatkan beberapa komponen komputasi awan:
```text
[Pelanggan (Web Browser)] ───► [Layanan Cloud Web App (PHP Native)]
                                     │            │
                                     ▼            ▼
                          [MySQL Database]   [Midtrans API Engine]
                                     ▲            │
                                     │            ▼
                          [Dasbor Admin]     [Webhook Callback Handler]
                                                  │
                                                  ▼
                                             [Layanan SMTP Relay Email]
```

#### 3.2 Desain Database (Relasional)
1. **`users`**: Menyimpan kredensial otorisasi berdasarkan hak akses/peran.
2. **`transactions`**: Menyimpan informasi utama pesanan, nominal harga, status dari Midtrans, dan lokasi invoice PDF.
3. **`api_logs`**: Menyimpan histori request dan response JSON payload mentah untuk kebutuhan debug.
4. **`payment_events`**: Menyimpan rekam jejak linimasa transaksi (contoh: pesanan dibuat -> Snap token dihasilkan -> pembayaran sukses -> email dikirim).
5. **`audit_logs`**: Menyimpan rekaman aktivitas admin untuk mematuhi standar keamanan monitoring cloud.

---

### BAB 4: IMPLEMENTASI SISTEM

#### 4.1 Struktur Kode Sumber
Aplikasi diorganisasikan ke dalam direktori terstruktur:
* `config/`: Menangani inisiasi database PDO, konfigurasi SMTP Mailer, dan parameter Midtrans.
* `admin/`: Menangani antarmuka dasbor monitoring, pencarian data, peninjauan webhook, serta ekspor file PDF/Excel.
* `assets/`: Menyimpan konfigurasi stylesheet CSS bertema gelap modern dan naskah JavaScript validasi form.
* `tools/`: Menampung diagnostik verifikasi konektivitas internet dan fungsionalitas email.

#### 4.2 Penerapan Variabel Lingkungan (.env)
Aplikasi memanfaatkan berkas `.env` untuk menghindari penyimpanan kredensial sensitif di dalam kode (*hardcoding*). Pustaka `vlucas/phpdotenv` dimanfaatkan untuk memuat variabel tersebut ke dalam superglobal `$_ENV` PHP.

---

### BAB 5: PENGUJIAN DAN EVALUASI

#### 5.1 Pengujian Integrasi Midtrans (Sandbox)
* **Kasus Uji**: Klik tombol "Bayar Sekarang" pada form checkout.
* **Hasil Diharapkan**: Sistem menyimpan data ke database dengan status `pending`, menembak API Midtrans, mendapatkan Snap Token, dan memicu modal popup Midtrans.
* **Hasil Pengujian**: Sukses. Popup SNAP Midtrans terbuka menampilkan virtual account simulator dan QRIS sandbox.

#### 5.2 Pengujian Webhook Callback & Notifikasi Email
* **Kasus Uji**: Pengguna menyelesaikan pembayaran simulasi di panel Midtrans (menghasilkan status `settlement`).
* **Hasil Diharapkan**: Midtrans mengirim callback POST ke `notification.php`. Sistem memvalidasi signature key, memperbarui status database menjadi `settlement`, membuat berkas PDF invoice, dan memicu SMTP mengirim email dengan lampiran invoice tersebut.
* **Hasil Pengujian**: Sukses. Callback diterima, status DB berubah menjadi `settlement`, invoice PDF tersimpan di server, dan email Mailtrap SMTP masuk lengkap dengan lampiran PDF.

#### 5.3 Pengujian Dasbor Realtime & Health Check
* **Kasus Uji**: Memantau tab monitoring saat webhook dikirim dan membuka tab spesifikasi server.
* **Hasil Diharapkan**: Baris tabel monitoring bertambah otomatis tanpa refresh halaman penuh. Sistem menampilkan spesifikasi OS hosting, versi PHP, memori terpakai, dan sisa disk space.
* **Hasil Pengujian**: Sukses. Ajax memuat log callback setiap 10 detik secara mulus. Informasi virtualisasi memory dan disk usage tertera secara akurat.

---

### BAB 6: KESIMPULAN

Proyek simulasi payment gateway ini membuktikan bahwa arsitektur komputasi awan yang terdistribusi dapat diimplementasikan dengan sangat baik menggunakan PHP Native. Konsep *Infrastructure as a Service (IaaS)* dan *Software as a Service (SaaS)* diilustrasikan melalui penggabungan web hosting, database cloud, layanan API pembayaran eksternal (Midtrans), dan layanan pengiriman email otomatis. Dasbor monitoring dan audit log memberikan kontrol visibilitas penuh bagi administrator, yang merupakan prasyarat mutlak dalam pengelolaan arsitektur aplikasi berbasis cloud modern.
