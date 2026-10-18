<?php
// ATASOFT ATC24 Radar - Airport Flights endpoint (Departures / Arrivals).
//
// Fetches airport departure or arrival flights from AWDEV API (/dep/{ICAO} or /arr/{ICAO}).
// Called as: airport-flights.php?airport=IRFD&type=dep
// Caches server-side for 6 seconds per upstream rate-limit guidelines.
// Uses file locking and fallback to stale cache on network failure.

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/inc/upstream.php';

$airport = strtoupper(trim($_GET['airport'] ?? ''));
if (!preg_match('/^[A-Z]{4}$/', $airport)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid airport code (must be 4 letters)']);
    exit;
}

$type = strtolower(trim($_GET['type'] ?? ''));
if ($type !== 'dep' && $type !== 'arr') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid flight type (must be "dep" or "arr")']);
    exit;
}

$upstreamUrl = "https://ws.awdevsoftware.org/{$type}/{$airport}";
$cacheFile   = __DIR__ . "/cache/{$type}_{$airport}.json";
$cacheMaxAge = 6; // seconds

$raw = atasoft_get_cached_curl($cacheFile, $upstreamUrl, $cacheMaxAge, 4);

if ($raw === null) {
    if (file_exists($cacheFile)) {
        echo file_get_contents($cacheFile);
        exit;
    }
    echo json_encode([]);
    exit;
}

echo $raw;
