<?php
// ATASOFT ATC24 Radar - published ATIS *list* endpoint.
//
// atis.php returns only the ATIS letter for one airport (all the ATIS panel
// needs). Centre mode needs more: the structured "DEP RWY .. ARR RWY .."
// line, because a route's approach line must be built toward the runway that
// is actually published in the ATIS - for every airport, not just the one the
// operator happens to have open.
//
// Same upstream the rest of this client already uses (see data.php,
// controllers.php, airport-flights.php), reached server-side so the browser
// doesn't make a cross-origin call. Returns the upstream array as-is
// (airport / letter / content / lines / editor).
//
// Called as atis-list.php with no parameters.
//
// ATASOFT: `?max_age=N` (1-60 seconds, default 10) is accepted so a caller that
// really wants the newest second can ask for it - the ATIS board does, because
// it refreshes once a second. The cache is still the rate limit: the value is
// clamped into range and one cache file serves every caller, so a page asking
// for a shorter age cannot make this endpoint fetch upstream more often than
// once per second.

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/inc/upstream.php';

const UPSTREAM_URL  = 'https://ws.awdevsoftware.org/atis';
const CACHE_FILE    = __DIR__ . '/cache/atis-list.json';
const CACHE_MAX_AGE = 10; // seconds - Centre only re-reads it every ~15s
const CACHE_MIN_AGE = 1;
const CACHE_MAX_AGE_ALLOWED = 60;

$maxAge = CACHE_MAX_AGE;
if (isset($_GET['max_age']) && is_numeric($_GET['max_age'])) {
    $maxAge = (int) $_GET['max_age'];
    if ($maxAge < CACHE_MIN_AGE) $maxAge = CACHE_MIN_AGE;
    if ($maxAge > CACHE_MAX_AGE_ALLOWED) $maxAge = CACHE_MAX_AGE_ALLOWED;
}

$dir = dirname(CACHE_FILE);
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}

$isStale = !file_exists(CACHE_FILE) || (time() - filemtime(CACHE_FILE)) >= $maxAge;
if ($isStale) {
    $lockHandle = fopen(CACHE_FILE . '.lock', 'c');
    if ($lockHandle && flock($lockHandle, LOCK_EX)) {
        clearstatcache(true, CACHE_FILE);
        $isStale = !file_exists(CACHE_FILE) || (time() - filemtime(CACHE_FILE)) >= $maxAge;
        if ($isStale) {
            // Same reasoning as atis.php: a transient upstream failure must not
            // wipe out a still-good cached copy - keep serving the last known
            // list and let the next request try again.
            $fresh = atasoft_fetch_upstream(UPSTREAM_URL);
            if ($fresh !== false && is_array(json_decode($fresh, true))) {
                file_put_contents(CACHE_FILE, $fresh, LOCK_EX);
            }
        }
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

if (!file_exists(CACHE_FILE)) {
    http_response_code(502);
    echo json_encode(['error' => 'Upstream ATIS list unavailable']);
    exit;
}

$list = json_decode(file_get_contents(CACHE_FILE), true);
if (!is_array($list)) {
    http_response_code(502);
    echo json_encode(['error' => 'Upstream returned invalid data']);
    exit;
}

echo json_encode(array_values($list));
