# Dokumentasi Arsitektur Sistem - CloudPay Sandbox

Dokumen ini memuat diagram arsitektur sistem, alur proses pembayaran (flowchart), Entity Relationship Diagram (ERD) database, dan sequence diagram integrasi API.

---

## 1. System Architecture Diagram
Diagram di bawah menunjukkan topologi dan hubungan antar komponen cloud dalam proyek simulasi ini.

```mermaid
graph TD
    User["Pelanggan (Web Browser)"]
    AdminUser["Administrator / Operator"]
    WebApp["Aplikasi Web (PHP Native)"]
    DB[("Database (MySQL/MariaDB)")]
    Midtrans["Midtrans Sandbox API"]
    SMTP["Layanan SMTP Email"]

    User -->|1. Akses Checkout & Pembayaran| WebApp
    WebApp -->|2. Request Snap Token| Midtrans
    Midtrans -->|3. Kirim Webhook Notification| WebApp
    WebApp -->|4. Simpan Data & Log| DB
    WebApp -->|5. Kirim Notifikasi Email| SMTP
    SMTP -->|6. Terima Email Tanda Terima| User
    AdminUser -->|7. Pantau Transaksi & System Info| WebApp
    DB -->|8. Sajikan Metrik Dashboard| WebApp
```

---

## 2. Flowchart Pembayaran
Diagram alur proses transaksi dari checkout hingga status pembayaran diperbarui di database.

```mermaid
flowchart TD
    Start([Mulai]) --> Form[Isi Form Checkout]
    Form --> Submit{Klik Bayar Sekarang}
    Submit --> DB_Pending[Simpan Transaksi Status 'pending']
    DB_Pending --> Request_Snap[Kirim Request Snap Token ke Midtrans]
    Request_Snap --> Snap_Popup[Tampilkan Snap Payment Popup]
    Snap_Popup --> Choose_Method[Pilih Metode Bayar Sandbox]
    Choose_Method --> Pay{Selesaikan Pembayaran?}
    
    Pay -->|Ya - Sukses| Webhook[Midtrans Kirim Callback HTTP POST ke notification.php]
    Pay -->|Tidak / Close| Redirect_Failed[Arahkan User ke failed.php]
    
    Webhook --> Verify_Sig{Verifikasi Signature Key Valid?}
    Verify_Sig -->|Tidak| Reject[Abaikan & Response HTTP 401]
    Verify_Sig -->|Ya| Update_DB[Update Transaksi Status 'settlement']
    Update_DB --> Invoice[Simpan File PDF Invoice]
    Invoice --> Email[Kirim Email HTML Invoice ke Pelanggan]
    Email --> Redirect_Success[Arahkan User ke success.php]
    
    Redirect_Success --> End([Selesai])
    Redirect_Failed --> End
```

---

## 3. Database Entity Relationship Diagram (ERD)
Struktur tabel relational database MySQL `payment_sandbox` yang digunakan dalam proyek.

```mermaid
erDiagram
    users {
        int id PK
        varchar username
        varchar password
        varchar role
        timestamp created_at
    }
    
    transactions {
        int id PK
        varchar order_id UK
        varchar customer_name
        varchar email
        varchar phone
        varchar product_name
        decimal amount
        varchar payment_type
        varchar transaction_status
        datetime transaction_time
        varchar snap_token
        varchar pdf_invoice_path
        timestamp created_at
        timestamp updated_at
    }

    api_logs {
        int id PK
        varchar endpoint
        longtext request_body
        longtext response_body
        timestamp created_at
    }

    payment_events {
        int id PK
        varchar order_id
        varchar event_name
        text event_data
        timestamp created_at
    }

    audit_logs {
        int id PK
        varchar username
        varchar action
        text details
        varchar ip_address
        timestamp created_at
    }

    transactions ||--o{ payment_events : "tracks lifecycle"
```

---

## 4. Sequence Diagram API Payment
Urutan interaksi komunikasi antar server (Client-Server & Server-to-Server Webhook).

```mermaid
sequenceDiagram
    autonumber
    actor User as Pelanggan
    participant Web as Web App (cPanel Server)
    participant DB as Database MySQL
    participant Mid as Midtrans Sandbox API
    participant Mail as SMTP Mail Server

    User->>Web: Isi Form & Klik "Bayar Sekarang"
    Web->>DB: Simpan order baru (Status: pending)
    Web->>Mid: POST /snap/v1/transactions (Payload Order & Amount)
    Mid-->>Web: Response JSON (Token Snap)
    Web->>DB: Update & Simpan Snap Token ke transaksi
    Web-->>User: Tampilkan Snap Payment Popup
    User->>Mid: Selesaikan Pembayaran di Panel Sandbox
    Mid-->>User: Pembayaran Sukses (Client Redirect)
    Note over User, Mid: Midtrans mengalihkan user secara asinkronus
    
    Mid->>Web: POST notification.php (Webhook Payload Settlement)
    Web->>Web: Verifikasi SHA-512 Signature Key
    Web->>DB: Update Transaksi (Status: settlement)
    Web->>Web: Generate Invoice PDF (Dompdf)
    Web->>Mail: Kirim Email Notifikasi (PHPMailer + PDF Attachment)
    Mail-->>User: Kirim Email Invoice Sukses
    Web-->>Mid: Response HTTP 200 (Callback Received)
```
