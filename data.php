<?php
// ATASOFT ATC24 Radar - aircraft data endpoint.
//
// Replaces the old Node/Express `GET /data` route. 24data's own API docs
// are explicit that browsers cannot call https://24data.ptfs.app directly
// (no permissive Allow-Origin header on their side) - it has to be fetched
// server-side and re-served to the page, which is exactly what this does.
//
// Cached in a flat file so that many browser tabs polling this endpoint
// every 3 seconds still only cause one real request to 24data every 3
// seconds, per their documented recommended rate (20 req/min) for
// GET /acft-data.

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/inc/upstream.php';

const UPSTREAM_URL   = 'https://24data.ptfs.app/acft-data';
const CACHE_FILE     = __DIR__ . '/cache/acft-data.json';
const CACHE_MAX_AGE  = 1; // seconds — 1s refresh for real-time radar
const ALT_THRESHOLD  = 100;
const GS_THRESHOLD   = 100;

// AWDEV auxiliary endpoints (6 second cache)
const FPLS_URL           = 'https://ws.awdevsoftware.org/fpls';
const FPLS_CACHE_FILE    = __DIR__ . '/cache/fpls.json';
const FPLS_CACHE_MAX_AGE = 6;

const EMERGENCY_URL           = 'https://ws.awdevsoftware.org/sse/acft-data';
const EMERGENCY_CACHE_FILE    = __DIR__ . '/cache/emergency.json';
const EMERGENCY_CACHE_MAX_AGE = 6;
const EMERGENCY_TIMEOUT_MS    = 800; // Under 1 sec to ensure data.php is never blocked

// ATASOFT: server-side full-flight trail store, so a browser that opens the
// radar mid-flight still sees the aircraft's route from takeoff instead of
// only points captured after that tab was open. This does NOT add any new
// upstream requests - it just records the position already fetched above,
// once per real 3s upstream refresh (never per-request), into a small JSON
// file. A callsign's trail is deleted the moment it disappears from the feed
// (flight over / left the server), same as the existing client-side
// behaviour, so nothing lingers for finished flights.
const TRAIL_FILE       = __DIR__ . '/cache/trails.json';
const TRAIL_MAX_POINTS = 3000; // high density trail points

// ATASOFT: server-side flight plan store. AWDEV's /fpls feed sometimes drops
// or invalidates a plan while its flight is still in progress, which used to
// make the plan (and everything searchable through it) vanish mid-flight.
// The first time an aircraft is matched to a plan, that plan is written here
// and served from here whenever the live feed no longer has it, until the
// aircraft has landed (was airborne, then stayed on the ground) or has been
// gone from the feed for a while. A newer plan for the same callsign
// (larger filedAt) always replaces the saved one. Plain JSON like the trail
// store - no SQLite/PDO extension needed on shared hosting.
const FPL_STORE_FILE   = __DIR__ . '/cache/fpl-store.json';
const FPL_STORE_TTL    = 600; // seconds an aircraft may be missing from the feed before its saved plan is dropped
const FPL_LANDED_AFTER = 20;  // seconds continuously on the ground, after having been airborne, = landed
const FPL_STORE_TOUCH  = 60;  // refresh an entry's lastSeen at most this often (avoids a file write per request)

// Returns the cached raw upstream body, refreshing it first if stale.
// Uses a non-blocking lock file so concurrent requests hitting an expired cache
// immediately receive the existing cache rather than piling up PHP worker threads.
function atasoft_get_cached(string $cacheFile, string $upstreamUrl, int $maxAge): ?string {
    $dir = dirname($cacheFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $fileExists = file_exists($cacheFile);
    $isStale = !$fileExists || (time() - filemtime($cacheFile)) >= $maxAge;
    if (!$isStale && $fileExists) {
        return @file_get_contents($cacheFile);
    }

    $lockHandle = @fopen($cacheFile . '.lock', 'c');
    if ($lockHandle) {
        // NON-BLOCKING LOCK: If another request is currently fetching upstream,
        // do NOT block! Immediately return the existing cached data.
        if (flock($lockHandle, LOCK_EX | LOCK_NB)) {
            clearstatcache(true, $cacheFile);
            $isStale = !file_exists($cacheFile) || (time() - filemtime($cacheFile)) >= $maxAge;
            if ($isStale) {
                $fresh = atasoft_fetch_upstream($upstreamUrl, 1, 3, 0);
                if ($fresh !== false && strlen($fresh) > 10) {
                    @file_put_contents($cacheFile, $fresh, LOCK_EX);
                }
            }
            flock($lockHandle, LOCK_UN);
        }
        @fclose($lockHandle);
    }

    return file_exists($cacheFile) ? @file_get_contents($cacheFile) : null;
}

// ATASOFT: updates and returns the saved flight plans, keyed by callsign.
// $states: callsign => ['fp' => live matched plan or null, 'ground' => bool].
// One request at a time writes (non-blocking lock, like the trail store);
// the others just read the last saved copy.
function atasoft_fpl_store_update(string $file, array $states): array {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $lockHandle = @fopen($file . '.lock', 'c');
    $canWrite = $lockHandle && flock($lockHandle, LOCK_EX | LOCK_NB);

    $store = [];
    $raw = file_exists($file) ? @file_get_contents($file) : false;
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $store = $decoded;
        }
    }

    if ($canWrite) {
        $before = json_encode($store);
        $now = time();

        foreach ($states as $callsign => $st) {
            $entry = $store[$callsign] ?? null;
            $fp = $st['fp'] ?? null;

            if (is_array($fp)) {
                $filedAt = (int)($fp['filedAt'] ?? 0);
                $savedFiledAt = is_array($entry) ? (int)($entry['filedAt'] ?? 0) : 0;
                if (!is_array($entry) || $filedAt > $savedFiledAt) {
                    // First sighting of this plan (or a newer one): save it.
                    $entry = [
                        'fp'          => $fp,
                        'filedAt'     => $filedAt,
                        'airborne'    => false,
                        'groundSince' => null,
                        'landed'      => false,
                        'lastSeen'    => $now,
                    ];
                } elseif (empty($entry['landed']) && $filedAt === $savedFiledAt) {
                    $entry['fp'] = $fp; // same plan - keep the saved copy current
                }
            }
            if (!is_array($entry)) {
                continue;
            }

            if (!empty($st['ground'])) {
                if (!empty($entry['airborne']) && empty($entry['landed'])) {
                    if (empty($entry['groundSince'])) {
                        $entry['groundSince'] = $now;
                    } elseif ($now - (int)$entry['groundSince'] >= FPL_LANDED_AFTER) {
                        $entry['landed'] = true; // flight over - stop serving the saved plan
                    }
                }
            } else {
                $entry['airborne'] = true;
                $entry['groundSince'] = null;
            }

            if ($now - (int)($entry['lastSeen'] ?? 0) >= FPL_STORE_TOUCH) {
                $entry['lastSeen'] = $now;
            }
            $store[$callsign] = $entry;
        }

        // Aircraft that left the feed a while ago: drop their saved plan.
        foreach ($store as $callsign => $entry) {
            if (!isset($states[$callsign]) && $now - (int)($entry['lastSeen'] ?? 0) > FPL_STORE_TTL) {
                unset($store[$callsign]);
            }
        }

        $after = json_encode($store);
        if ($after !== $before) {
            @file_put_contents($file, $after, LOCK_EX);
        }
    }

    if ($lockHandle) {
        if ($canWrite) {
            flock($lockHandle, LOCK_UN);
        }
        @fclose($lockHandle);
    }

    return $store;
}

// ATASOFT: load + (if a fresh upstream fetch just happened) update the
// server-side trail store. Locked the same way as the upstream cache so
// concurrent requests don't corrupt the file or double-append a point.
function atasoft_update_trails(string $trailFile, bool $isFreshFetch, array $aircraftRaw, array $enrichedByCallsign, int $maxPoints): array {
    $dir = dirname($trailFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $lockHandle = @fopen($trailFile . '.lock', 'c');
    $trails = [];
    if ($lockHandle && flock($lockHandle, LOCK_EX | LOCK_NB)) {
        $raw = file_exists($trailFile) ? file_get_contents($trailFile) : null;
        $store = $raw ? json_decode($raw, true) : null;
        if (!is_array($store) || !isset($store['trails'])) {
            $store = ['trails' => []];
        }

        if ($isFreshFetch) {
            $updated = [];
            foreach ($aircraftRaw as $callsign => $ac) {
                $pos = $ac['position'] ?? null;
                if (!is_array($pos) || !isset($pos['x'], $pos['y'])) {
                    continue;
                }
                $info = $enrichedByCallsign[$callsign] ?? null;
                $onGround = $info && $info['isOnGround'] === true;

                $points = $store['trails'][$callsign] ?? [];
                $points[] = [
                    round($pos['x'], 1),
                    round($pos['y'], 1),
                    isset($ac['altitude']) ? (int) round($ac['altitude']) : null,
                    $onGround ? 1 : 0,
                ];
                if (count($points) > $maxPoints) {
                    // Drop the oldest ~20% in one go instead of shifting a
                    // single point every cycle - much cheaper to write.
                    $points = array_slice($points, (int) ($maxPoints * 0.2));
                }
                $updated[$callsign] = $points;
            }
            // Any callsign missing from $aircraftRaw this cycle has landed
            // and left the feed / the flight is over - not carrying it into
            // $updated is exactly "delete this route from the database".
            $store['trails'] = $updated;
            file_put_contents($trailFile, json_encode($store), LOCK_EX);
        }

        $trails = $store['trails'] ?? [];
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }

    return $trails;
}

$raw = atasoft_get_cached(CACHE_FILE, UPSTREAM_URL, CACHE_MAX_AGE);

if ($raw === null) {
    http_response_code(502);
    echo json_encode(['error' => 'Upstream 24data.ptfs.app unavailable']);
    exit;
}

$aircraftRaw = json_decode($raw, true);
if (!is_array($aircraftRaw)) {
    http_response_code(502);
    echo json_encode(['error' => 'Upstream returned invalid data']);
    exit;
}

// A trail point should only be recorded once per real upstream refresh
// (every ~3s), never once per browser request - this file's mtime IS the
// upstream cache's freshness clock, so "did clearstatcache-fresh mtime
// change since our own last write" tells us that cleanly without a second
// mechanism to keep in sync.
clearstatcache(true, CACHE_FILE);
$cacheMtime      = file_exists(CACHE_FILE) ? filemtime(CACHE_FILE) : time();
$trailMarkerFile = CACHE_FILE . '.trailmark';
$lastMarked      = file_exists($trailMarkerFile) ? (int) file_get_contents($trailMarkerFile) : 0;
$isFreshFetch    = $cacheMtime > $lastMarked;
if ($isFreshFetch) {
    @file_put_contents($trailMarkerFile, (string) $cacheMtime, LOCK_EX);
}

// Load auxiliary FPL data from AWDEV API (6s flat-file cache with locking)
$fplsRaw = atasoft_get_cached_curl(FPLS_CACHE_FILE, FPLS_URL, FPLS_CACHE_MAX_AGE, 2);
$fplsList = $fplsRaw ? json_decode($fplsRaw, true) : null;
$fplMap = [];
$fplNumberMap = [];

if (is_array($fplsList)) {
    foreach ($fplsList as $fp) {
        $realCallsign = trim((string)($fp['realcallsign'] ?? ''));
        if ($realCallsign === '') {
            continue;
        }

        // Format flightlevel: ensure it starts with "FL" once and only once
        $fl = trim((string)($fp['flightlevel'] ?? ''));
        if ($fl !== '') {
            if (!preg_match('/^FL/i', $fl)) {
                $fl = 'FL' . $fl;
            } else {
                $fl = 'FL' . preg_replace('/^FL/i', '', $fl);
            }
        }
        $fp['flightlevel'] = $fl;

        $filedAt = (int)($fp['filedAt'] ?? 0);

        // Normalize keys for resilient matching against 24data callsign keys
        $exactKey = $realCallsign;
        $normKey = strtolower(preg_replace('/[\s_-]+/', '-', $realCallsign));

        // If duplicate realcallsign exists, keep the newest (largest filedAt)
        if (!isset($fplMap[$exactKey]) || $filedAt > ((int)($fplMap[$exactKey]['filedAt'] ?? 0))) {
            $fplMap[$exactKey] = $fp;
        }
        if (!isset($fplMap[$normKey]) || $filedAt > ((int)($fplMap[$normKey]['filedAt'] ?? 0))) {
            $fplMap[$normKey] = $fp;
        }

        // The plan's own "callsign" (what the pilot filed, e.g. Delta-39W) is
        // indexed too, but only where no realcallsign already claims the key.
        $ownCallsign = trim((string)($fp['callsign'] ?? ''));
        if ($ownCallsign !== '') {
            $ownNormKey = strtolower(preg_replace('/[\s_-]+/', '-', $ownCallsign));
            if (!isset($fplMap[$ownNormKey])) {
                $fplMap[$ownNormKey] = $fp;
            }
        }

        // Precompute flight number index (digits only, e.g. "1781") for O(1) matching
        if (preg_match('/(\d+)/', $realCallsign, $numMatches)) {
            $numKey = $numMatches[1];
            if (!isset($fplNumberMap[$numKey]) || $filedAt > ((int)($fplNumberMap[$numKey]['filedAt'] ?? 0))) {
                $fplNumberMap[$numKey] = $fp;
            }
        }
    }
}

// Load auxiliary emergency data (/sse/acft-data) with <=800ms abort to prevent blocking
$emergencyCache = atasoft_fetch_sse_emergency(EMERGENCY_CACHE_FILE, EMERGENCY_URL, EMERGENCY_CACHE_MAX_AGE, EMERGENCY_TIMEOUT_MS);

// ATASOFT: pass 1 - match every aircraft to a live plan and note whether it is
// on the ground, then let the plan store save new plans / retire landed ones.
$fplStates = [];
foreach ($aircraftRaw as $callsign => $ac) {
    $altitude    = $ac['altitude'] ?? null;
    $groundSpeed = $ac['groundSpeed'] ?? null;

    // Match flight plan: "realcallsign" matches 24data main key
    // Strategy 1: exact key match
    // Strategy 2: normalized key match (lowercase, dashes)
    // Strategy 3: O(1) number match - flight number digits (PTFS unique flight number)
    $liveFp = null;
    $normCallsign = strtolower(preg_replace('/[\s_-]+/', '-', $callsign));
    if (isset($fplMap[$callsign])) {
        $liveFp = $fplMap[$callsign];
    } elseif (isset($fplMap[$normCallsign])) {
        $liveFp = $fplMap[$normCallsign];
    } elseif (preg_match('/(\d+)/', $callsign, $keyDigits) && isset($fplNumberMap[$keyDigits[1]])) {
        $liveFp = $fplNumberMap[$keyDigits[1]];
    }

    $computedGround = ($altitude !== null && $groundSpeed !== null)
        ? ($altitude < ALT_THRESHOLD && $groundSpeed < GS_THRESHOLD)
        : null;
    $fplStates[$callsign] = [
        'fp'     => $liveFp,
        'ground' => ($computedGround === true || ($ac['isOnGround'] ?? null) === true),
    ];
}
$fplStore = atasoft_fpl_store_update(FPL_STORE_FILE, $fplStates);

$enriched = [];
foreach ($aircraftRaw as $callsign => $ac) {
    $altitude    = $ac['altitude'] ?? null;
    $groundSpeed = $ac['groundSpeed'] ?? null;
    // Per 24data's docs, isOnGround is absent for helicopter aircraft data.
    $rawOnGround = $ac['isOnGround'] ?? null;
    $normCallsign = strtolower(preg_replace('/[\s_-]+/', '-', $callsign));

    // Live plan if the feed still has one, otherwise the plan saved when this
    // flight was first matched (kept until the aircraft has landed).
    $matchedFp = $fplStates[$callsign]['fp'] ?? null;
    if ($matchedFp === null
        && isset($fplStore[$callsign])
        && empty($fplStore[$callsign]['landed'])
        && is_array($fplStore[$callsign]['fp'] ?? null)) {
        $matchedFp = $fplStore[$callsign]['fp'];
    }

    $flightStatus = 'No FLP';
    if ($matchedFp !== null) {
        if ($altitude !== null && $altitude < ALT_THRESHOLD && $groundSpeed !== null && $groundSpeed < GS_THRESHOLD) {
            $flightStatus = 'Parked';
        } elseif ($rawOnGround) {
            $flightStatus = 'Taxiing';
        } else {
            $flightStatus = 'In Flight';
        }
    }

    // Emergency verification: 24data emergency OR SSE emergency cache
    $isEmergency = (!empty($ac['isEmergencyOccuring']))
        || (!empty($emergencyCache[$callsign]))
        || (!empty($emergencyCache[$normCallsign]));

    $enriched[$callsign] = [
        'heading'             => $ac['heading'] ?? null,
        'altitude'            => $altitude,
        'playerName'          => $ac['playerName'] ?? null,
        'aircraftType'        => $ac['aircraftType'] ?? null,
        'position'            => $ac['position'] ?? null,
        'speed'               => $ac['speed'] ?? null,
        'wind'                => $ac['wind'] ?? null,
        'isOnGround'          => ($altitude !== null && $groundSpeed !== null)
            ? ($altitude < ALT_THRESHOLD && $groundSpeed < GS_THRESHOLD)
            : null,
        'isTaxiing'           => $rawOnGround,
        'groundSpeed'         => $groundSpeed,
        'isEmergencyOccuring' => $isEmergency,
        'flightPlan'          => $matchedFp,
        'flightStatus'        => $flightStatus,
        'trail'               => [],
    ];
}

$trails = atasoft_update_trails(TRAIL_FILE, $isFreshFetch, $aircraftRaw, $enriched, TRAIL_MAX_POINTS);
foreach ($trails as $callsign => $points) {
    if (isset($enriched[$callsign])) {
        $enriched[$callsign]['trail'] = $points;
    }
}

echo json_encode($enriched);
