<?php
require_once 'includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudPay Sandbox - Home Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            padding: 100px 0 80px 0;
            position: relative;
        }
        .feature-card {
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .feature-card:hover {
            background: rgba(99, 102, 241, 0.05);
            border-color: var(--primary);
            transform: translateY(-5px);
        }
        .step-bubble {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 15px;
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .flow-node {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        .flow-arrow {
            text-align: center;
            font-size: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <span class="badge bg-primary px-3 py-2 rounded-pill mb-3" style="font-size: 0.85rem; letter-spacing: 0.05em;">
                        PROYEK AKHIR VIRTUALISASI CLOUD COMPUTING
                    </span>
                    <h1 class="display-4 fw-bold text-white mb-3" style="font-family: 'Outfit', sans-serif;">
                        Sistem Simulasi Cloud Integrasi <br>
                        <span style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Payment Gateway Sandbox</span>
                    </h1>
                    <p class="text-secondary fs-5 mb-5 mx-auto col-md-10">
                        Sebuah platform demonstrasi komputasi awan terpadu yang memvisualisasikan komunikasi API Snap, monitoring database as-a-service (DBaaS), live monitoring webhook callback, dan relay SMTP email otomatis.
                    </p>
                    
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="checkout.php" class="btn btn-gradient-primary btn-lg px-5 py-3">
                            <i class="bi bi-rocket-takeoff-fill me-2"></i>Mulai Simulasi Pembayaran
                        </a>
                        <a href="admin/login.php" class="btn btn-outline-custom btn-lg px-4 py-3">
                            <i class="bi bi-shield-lock-fill me-2"></i>Buka Admin Portal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Concept Section -->
    <section class="container my-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-white">Prinsip & Konsep Cloud yang Diterapkan</h2>
            <p class="text-secondary">Arsitektur aplikasi terintegrasi ini dirancang sesuai capaian pembelajaran mata kuliah.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card h-100">
                    <div class="fs-1 text-primary mb-3"><i class="bi bi-cloud-arrow-up"></i></div>
                    <h5 class="text-white fw-bold">Cloud Hosted App</h5>
                    <p class="text-secondary small mb-0">
                        Kode program PHP Native portabel, siap untuk langsung dideploy ke hosting cPanel melalui sistem virtualisasi shared hosting cloud.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100">
                    <div class="fs-1 text-secondary mb-3"><i class="bi bi-database-check"></i></div>
                    <h5 class="text-white fw-bold">Database as a Service</h5>
                    <p class="text-secondary small mb-0">
                        Penerapan MySQL/MariaDB dengan driver PDO untuk mencatat data transaksi secara terstruktur serta mengamankan query dari SQL Injection.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100">
                    <div class="fs-1 text-success mb-3"><i class="bi bi-envelope-paper-heart"></i></div>
                    <h5 class="text-white fw-bold">SMTP Relay System</h5>
                    <p class="text-secondary small mb-0">
                        Integrasi PHPMailer dengan server pengiriman SMTP untuk mengirimkan invoice receipt dalam format HTML ke email pembeli secara instan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Live System Architecture Flowchart (Interactive UI) -->
    <section class="container my-5 py-5 border-top border-secondary">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-white"><i class="bi bi-diagram-3 me-2"></i>Visualisasi Aliran Integrasi API</h2>
            <p class="text-secondary">Peta perjalanan data pembayaran digital secara server-to-server.</p>
        </div>

        <div class="row g-3 justify-content-center align-items-center">
            <div class="col-md-2">
                <div class="flow-node">
                    <span class="fs-2 text-primary d-block mb-2"><i class="bi bi-phone"></i></span>
                    <strong class="text-white d-block" style="font-size:0.85rem;">Client Browser</strong>
                    <small class="text-secondary" style="font-size:0.75rem;">Form Checkout</small>
                </div>
            </div>
            <div class="col-md-1 flow-arrow">
                <i class="bi bi-arrow-right"></i>
            </div>
            <div class="col-md-2">
                <div class="flow-node">
                    <span class="fs-2 text-white d-block mb-2"><i class="bi bi-hdd-network"></i></span>
                    <strong class="text-white d-block" style="font-size:0.85rem;">cPanel Web Server</strong>
                    <small class="text-secondary" style="font-size:0.75rem;">Aplikasi PHP</small>
                </div>
            </div>
            <div class="col-md-1 flow-arrow">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <div class="col-md-2">
                <div class="flow-node" style="border-color: var(--primary);">
                    <span class="fs-2 text-primary d-block mb-2"><i class="bi bi-credit-card-2-front"></i></span>
                    <strong class="text-primary d-block" style="font-size:0.85rem;">Midtrans Sandbox</strong>
                    <small class="text-secondary" style="font-size:0.75rem;">Payment Gateway</small>
                </div>
            </div>
            <div class="col-md-1 flow-arrow">
                <i class="bi bi-arrow-right"></i>
            </div>
            <div class="col-md-2">
                <div class="flow-node">
                    <span class="fs-2 text-success d-block mb-2"><i class="bi bi-send-check"></i></span>
                    <strong class="text-white d-block" style="font-size:0.85rem;">SMTP Email Relay</strong>
                    <small class="text-secondary" style="font-size:0.75rem;">Invoice Dikirim</small>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works section -->
    <section class="container my-5 py-5 border-top border-secondary">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-white">Cara Melakukan Demo Presentasi</h2>
            <p class="text-secondary">Langkah-langkah yang direkomendasikan untuk menunjukkan hasil tugas ke dosen pembimbing.</p>
        </div>

        <div class="row g-4 text-start">
            <div class="col-md-3">
                <div class="card card-custom p-4 h-100">
                    <span class="step-bubble">1</span>
                    <h5 class="text-white fw-bold">Inisiasi Data Seeding</h5>
                    <p class="text-secondary small mb-0">Jalankan script database seeder untuk langsung mengisi data transaksi dummy 30 hari ke belakang.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-4 h-100">
                    <span class="step-bubble">2</span>
                    <h5 class="text-white fw-bold">Simulasi Transaksi</h5>
                    <p class="text-secondary small mb-0">Lakukan pembelian produk lewat checkout page lalu selesaikan pembayaran sandbox.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-4 h-100">
                    <span class="step-bubble">3</span>
                    <h5 class="text-white fw-bold">Pantau Callback Live</h5>
                    <p class="text-secondary small mb-0">Tunjukkan menu Live Callback Monitor di admin panel yang menangkap event webhook masuk otomatis.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-4 h-100">
                    <span class="step-bubble">4</span>
                    <h5 class="text-white fw-bold">Verifikasi Laporan</h5>
                    <p class="text-secondary small mb-0">Tunjukkan dokumen invoice PDF yang diunduh langsung serta ekspor spreadsheet Excel data transaksi.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p class="mb-1"><strong>CloudPay Sandbox Simulator</strong> &copy; 2026. Tugas Akhir Virtualisasi Cloud Computing.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
