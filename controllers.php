<?php
// ATASOFT ATC24 Radar - Online Controllers endpoint.
//
// Fetches online controller positions from AWDEV API (GET /controllers).
// Caches server-side for 6 seconds per upstream rate-limit guidelines.
// Uses file locking and fallback to stale cache on network failure.

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/inc/upstream.php';

const UPSTREAM_URL  = 'https://ws.awdevsoftware.org/controllers';
const CACHE_FILE    = __DIR__ . '/cache/controllers.json';
const CACHE_MAX_AGE = 6; // seconds

$raw = atasoft_get_cached_curl(CACHE_FILE, UPSTREAM_URL, CACHE_MAX_AGE, 4);

if ($raw === null) {
    if (file_exists(CACHE_FILE)) {
        echo file_get_contents(CACHE_FILE);
        exit;
    }
    echo json_encode([]);
    exit;
}

echo $raw;
