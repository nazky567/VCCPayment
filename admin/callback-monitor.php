<?php
define('IS_ADMIN_DIR', true);
require_once __DIR__ . '/../includes/auth_check.php';
enforceAdmin();
require_once __DIR__ . '/../config/database.php';

$role = $_SESSION['role'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Callback Monitor - CloudPay Sandbox</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Header / Navbar -->
    <?php require_once '../includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid px-4 my-4">
        
        <div class="d-md-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1"><i class="bi bi-terminal-dash text-primary me-2"></i>Live Webhook Callback Monitor</h3>
                <p class="text-secondary mb-0">Memantau request callback HTTP POST masuk dari Midtrans API secara realtime (Refresh otomatis 10 detik).</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <div class="d-flex align-items-center text-secondary small me-2">
                    <span class="pulse-indicator online me-2"></span> Monitoring Aktif
                </div>
                <button class="btn btn-outline-custom text-primary border-primary btn-sm py-2 px-3" onclick="refreshMonitor()"><i class="bi bi-arrow-clockwise"></i> Refresh Manual</button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Table Monitor -->
            <div class="col-lg-7">
                <div class="card card-custom">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold"><i class="bi bi-terminal text-primary me-2"></i>Log Request Webhook Terakhir</h5>
                        <small class="text-secondary" id="last-update-time">Terakhir diperbarui: -</small>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-custom align-middle" style="font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Endpoint</th>
                                        <th>Order ID</th>
                                        <th>Preview Payload</th>
                                        <th style="text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="monitor-tbody">
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-5">
                                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Mengambil data monitoring...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inspect Details Panel -->
            <div class="col-lg-5">
                <div class="card card-custom h-100">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-search text-secondary me-2"></i>Inspeksi JSON Body</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-secondary small">Pilih salah satu baris log di samping untuk melakukan inspeksi data request body secara mendalam.</p>
                        
                        <div id="inspect-panel" style="display: none;">
                            <div class="mb-3">
                                <label class="text-secondary small">API Endpoint</label>
                                <h6 id="inspect-endpoint" class="text-primary fw-bold">/notification-callback</h6>
                            </div>
                            <div class="mb-3">
                                <label class="text-secondary small">Waktu Callback</label>
                                <p id="inspect-time" class="fw-semibold text-white">2026-06-10 12:00:00</p>
                            </div>
                            
                            <hr class="border-secondary my-3">
                            
                            <div class="mb-3">
                                <label class="text-secondary small">Request Body (JSON Payload)</label>
                                <pre id="inspect-request" class="json-box" style="height: 180px;"></pre>
                            </div>
                            <div>
                                <label class="text-secondary small">Response Output (JSON)</label>
                                <pre id="inspect-response" class="json-box" style="height: 80px;"></pre>
                            </div>
                        </div>
                        
                        <div id="inspect-placeholder" class="text-center py-5">
                            <i class="bi bi-file-code fs-1 text-secondary mb-3 d-block"></i>
                            <span class="text-secondary">Pilih baris log webhook untuk menampilkan payload body.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer>
        <div class="container-fluid px-4 text-center">
            <p class="mb-1"><strong>CloudPay Sandbox Simulator</strong> &copy; 2026. Dashboard Monitoring Cloud Virtualization.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            refreshMonitor();
            // Start auto refresh interval: 10 seconds (10000ms)
            setInterval(refreshMonitor, 10000);
        });

        let loadedLogs = [];

        function refreshMonitor() {
            fetch('get_callbacks_ajax.php')
                .then(response => response.json())
                .then(data => {
                    loadedLogs = data;
                    renderTable(data);
                    document.getElementById('last-update-time').textContent = 'Terakhir diperbarui: ' + new Date().toLocaleTimeString('id-ID');
                })
                .catch(error => {
                    console.error('Error fetching callback data:', error);
                });
        }

        function renderTable(data) {
            const tbody = document.getElementById('monitor-tbody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-5"><i class="bi bi-inbox fs-3 mb-2 d-block"></i>Belum ada callback log terekam.</td></tr>';
                return;
            }
            
            let html = '';
            data.forEach((log, index) => {
                let orderId = 'N/A';
                try {
                    const reqObj = JSON.parse(log.request_body);
                    if (reqObj && reqObj.order_id) {
                        orderId = reqObj.order_id;
                    }
                } catch(e) {}

                // Shorten request preview
                let preview = log.request_body.length > 35 ? log.request_body.substring(0, 35) + '...' : log.request_body;
                
                html += `<tr>
                    <td class="text-secondary">${log.created_at}</td>
                    <td><code class="text-primary">${log.endpoint}</code></td>
                    <td class="fw-semibold text-white">${orderId}</td>
                    <td class="text-secondary font-monospace" style="font-size:0.75rem;">${preview}</td>
                    <td style="text-align: center;">
                        <button class="btn btn-outline-custom btn-sm py-1 px-2.5" onclick="inspectLog(${index})">
                            <i class="bi bi-eye"></i> Inspeksi
                        </button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        function inspectLog(index) {
            const log = loadedLogs[index];
            if (!log) return;
            
            document.getElementById('inspect-placeholder').style.display = 'none';
            document.getElementById('inspect-panel').style.display = 'block';
            
            document.getElementById('inspect-endpoint').textContent = log.endpoint;
            document.getElementById('inspect-time').textContent = log.created_at + ' WIB';
            
            // Format JSON Request
            try {
                const reqObj = JSON.parse(log.request_body);
                document.getElementById('inspect-request').textContent = JSON.stringify(reqObj, null, 4);
            } catch (e) {
                document.getElementById('inspect-request').textContent = log.request_body;
            }

            // Format JSON Response
            try {
                const resObj = JSON.parse(log.response_body);
                document.getElementById('inspect-response').textContent = JSON.stringify(resObj, null, 4);
            } catch (e) {
                document.getElementById('inspect-response').textContent = log.response_body;
            }
        }
    </script>
</body>
</html>
