<?php
// ATASOFT ATC24 Radar - Airline Logos endpoint.
// Proxies public/assets/airline-logos.json to avoid static JSON 403/Forbidden
// restrictions on Apache/cPanel shared hosting environments.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

$file = __DIR__ . '/public/assets/airline-logos.json';
if (file_exists($file) && is_readable($file)) {
    readfile($file);
    exit;
}

echo '{}';
