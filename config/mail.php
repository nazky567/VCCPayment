<?php
require_once __DIR__ . '/database.php';

function getPHPMailerInstance() {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Fallback logger helper when composer vendor is not installed yet
        return new class {
            private $to = [];
            private $subject = '';
            private $body = '';
            private $isHTML = false;
            
            public function isSMTP() {}
            public function setFrom($email, $name = '') {}
            public function addAddress($email, $name = '') { $this->to[] = $email; }
            public function isHTML($isHTML = true) { $this->isHTML = $isHTML; }
            public function Subject($subject) { $this->subject = $subject; } // deprecated but set directly
            public $Subject = '';
            public $Body = '';
            public $AltBody = '';
            
            public function send() {
                $logDir = __DIR__ . '/../logs';
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0777, true);
                }
                $subject = $this->Subject ?: $this->subject;
                $body = $this->Body ?: $this->body;
                $logContent = "[" . date('Y-m-d H:i:s') . "] EMAIL TO: " . implode(', ', $this->to) . "\n";
                $logContent .= "SUBJECT: " . $subject . "\n";
                $logContent .= "HTML: " . ($this->isHTML ? 'Yes' : 'No') . "\n";
                $logContent .= "BODY:\n" . $body . "\n";
                $logContent .= "--------------------------------------------------\n";
                file_put_contents($logDir . '/email.log', $logContent, FILE_APPEND);
                return true;
            }
        };
    }
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = getenv('SMTP_HOST') ?: 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('SMTP_USER') ?: '';
    $mail->Password   = getenv('SMTP_PASS') ?: '';
    $mail->SMTPSecure = getenv('SMTP_ENCRYPTION') ?: 'tls';
    $mail->Port       = getenv('SMTP_PORT') ?: 2525;
    
    // Disable SSL verification for local environment compatibility
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Default Sender
    $mail->setFrom(
        getenv('SMTP_FROM_EMAIL') ?: 'noreply@sandbox-payment.cloud',
        getenv('SMTP_FROM_NAME') ?: 'Cloud Payment Sandbox'
    );
    
    $mail->CharSet = 'UTF-8';
    
    return $mail;
}
