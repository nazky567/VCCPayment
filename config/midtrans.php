<?php
require_once __DIR__ . '/database.php'; // Ensure env is loaded

$server_key = getenv('MIDTRANS_SERVER_KEY') ?: '';
$client_key = getenv('MIDTRANS_CLIENT_KEY') ?: '';
$is_production = filter_var(getenv('MIDTRANS_IS_PRODUCTION') ?: false, FILTER_VALIDATE_BOOLEAN);

if (class_exists('\Midtrans\Config')) {
    \Midtrans\Config::$serverKey = $server_key;
    \Midtrans\Config::$clientKey = $client_key;
    \Midtrans\Config::$isProduction = $is_production;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;
}

// Return key configurations for custom integration if needed
return [
    'serverKey' => $server_key,
    'clientKey' => $client_key,
    'isProduction' => $is_production,
    'isConfigured' => (!empty($server_key) && $server_key !== 'SB-Mid-server-placeholder')
];
