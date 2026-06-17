<?php
// Load composer autoloader if exists
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Custom simple fallback .env loader in case composer install hasn't run yet
if (!class_exists('Dotenv\Dotenv')) {
    $envPath = __DIR__ . '/../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                // Strip quotes if present
                if (preg_match('/^"#(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                }
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
} else {
    // If Dotenv exists, load it using Unsafe wrapper to populate getenv()
    try {
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    } catch (\Exception $e) {
        // Safe load does not throw on missing files
    }
}

// Database configuration variables
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'payment_sandbox';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Database Connection Failure: " . $e->getMessage());
    // Safe response for web page
    header('Content-Type: text/html; charset=utf-8');
    echo "<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;background:#fce8e6;color:#a82c2c;border-radius:8px;border:1px solid #f5c2c2;'>";
    echo "<h2 style='margin-top:0;'>Database Connection Error</h2>";
    echo "<p>Could not connect to the database. Please make sure that your <code>.env</code> file has the correct database configuration and that the database server is running.</p>";
    echo "<hr style='border-color:#f5c2c2;'>";
    echo "<small>Error info: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</small>";
    echo "</div>";
    exit;
}
