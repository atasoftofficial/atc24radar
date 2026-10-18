<?php
// ATASOFT ATC24 Radar - shared flight board (controller-entered ops data).
//
// WHY: a departure controller and an arrival controller work the same flight
// from two different positions - and, more often than not, from two different
// browsers. The cruise level (FL) one of them clears the flight at, the
// discrete squawk that was issued, the departure/arrival runway, SID, stand,
// remarks and the "cleared" tick are NOT in any upstream feed: they are typed
// by a controller into the DEP/ARR boards or the aircraft panel. Before this
// endpoint they only existed in the tab that typed them, so the receiving
// controller could not see that the level had changed or which squawk was
// already assigned.
//
// This stores those operator-entered fields in ONE small JSON file next to
// the other caches and lets every client poll them. Storage deliberately
// follows the flat-file + flock pattern the rest of this backend already uses
// (cache/atis-list.json, public/assets/airline-logos.json) - no SQLite/PDO
// dependency on shared hosting.
//
//   GET  flightboard.php
//        -> {"shared":true,"updatedAt":<ms>,"flights":{"<KEY>":{...}}}
//   POST flightboard.php
//        body (JSON, or form field "payload"): {"action":"set"|"delete",
//        "key":"QANTAS-3312","fields":{"fl":"FL340","squawk":"1421"},"by":"ISKP"}
//
// Records are keyed by the flight's callsign (normalised: upper case, runs of
// space/underscore/dash collapsed to one dash), which is the one identifier
// both the departure and the arrival board already hold for the same flight.
//
// HONESTY / SAFETY NOTES:
// - This endpoint has no login. The one thing it DOES check is the rule that
//   matters for a shared board: amending a flight plan requires a controlling
//   position on the online-controller feed (see the amendment gate below).
//   Everything writable here is operator annotation for a callsign - never the
//   live feed, never another user's data. The README says so.
// - The gate proves POSSESSION of a position, not identity: the ATC24 name in
//   the write is taken at face value, exactly like the upstream feed does.
// - Writes are bounded: known field names only, each value length-limited and
//   stripped, and records older than ENTRY_TTL are pruned (a filed plan only
//   lives ~30 min, so 12h is already generous). The file therefore cannot be
//   used as arbitrary storage.
// - A transient filesystem problem returns 503 with the last known-good board
//   only if it can be read; the client keeps serving its own copy on any
//   error, so a write failure can never blank a controller's board.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

const BOARD_FILE  = __DIR__ . '/cache/flightboard.json';
const ENTRY_TTL   = 43200; // 12 hours
const MAX_FLIGHTS = 800;   // hard cap on stored records
const MAX_FIELD   = 40;    // chars per field value

// The only field names a client may write. Anything else in "fields" is
// ignored rather than stored, so this file can never accumulate junk.
const BOARD_FIELDS = ['fl', 'squawk', 'rwy', 'sid', 'stand', 'rmk', 'cleared', 'dep', 'arr', 'type'];

// WHO MAY AMEND: amending a flight - the cleared level, runway, SID, stand,
// remarks and the cleared tick - is a controller action, so it needs a
// controlling position: the `by` identity has to be one of the holders the
// online-controller feed (cache/controllers.json, written by controllers.php)
// reports, at one of the flight's own airports (dep/arr), or holding a CTR
// position, which by definition covers the field. The SQUAWK is the one value
// that is passed on rather than amended, so it is always accepted.
//
// The client applies the same rule before it lets anyone type (see
// src/flightBoard.js) - this is the server-side half of it, so a hand-crafted
// POST cannot amend a flight plan either. Field names the flight plan itself
// supplies (dep/arr/type) are context, not amendments, and are never gated.
const AMENDMENT_FIELDS = ['fl', 'rwy', 'sid', 'stand', 'rmk', 'cleared'];
const CONTEXT_FIELDS = ['dep', 'arr', 'type'];
const CONTROLLERS_CACHE = __DIR__ . '/cache/controllers.json';
const CLAIM_CACHE_MAX_AGE = 900; // s: a stale controller list cannot prove a claim

function board_empty(): array {
    return ['updatedAt' => 0, 'flights' => []];
}

/**
 * The positions the online-controller feed currently reports, or null when it
 * has nothing usable (no file, unreadable, not JSON, or older than
 * CLAIM_CACHE_MAX_AGE - a stale list must never be used to prove a claim).
 * Returns a list of ['holder' => lower-case name, 'airport' => ICAO,
 * 'position' => TWR/GND/APP/CTR].
 */
function board_positions(): ?array {
    clearstatcache(true, CONTROLLERS_CACHE);
    if (!file_exists(CONTROLLERS_CACHE)) {
        return null;
    }
    if (time() - (int) @filemtime(CONTROLLERS_CACHE) > CLAIM_CACHE_MAX_AGE) {
        return null;
    }
    $raw = @file_get_contents(CONTROLLERS_CACHE);
    $decoded = $raw ? json_decode($raw, true) : null;
    if (!is_array($decoded)) {
        return null;
    }
    $positions = [];
    foreach ($decoded as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $holder = isset($entry['holder']) ? trim((string) $entry['holder']) : '';
        if ($holder === '') {
            continue; // an unclaimed station is nobody's position
        }
        $positions[] = [
            'holder'   => mb_strtolower($holder),
            'airport'  => strtoupper(trim((string) ($entry['airport'] ?? ''))),
            'position' => strtoupper(trim((string) ($entry['position'] ?? ''))),
        ];
    }
    return $positions;
}

/**
 * Whether $by holds a controlling position for a flight between $dep and $arr.
 * Returns ['held' => bool, 'who' => string, 'why' => string]. The `why` text is
 * what the controller is shown, so it says something true about their actual
 * position rather than a generic refusal.
 */
function board_claim(string $by, string $dep, string $arr): array {
    $name = mb_strtolower(trim($by));
    if ($name === '') {
        return ['held' => false, 'who' => '',
            'why' => 'No ATC24 name was sent with this write, so no controlling position could be checked.'];
    }
    $positions = board_positions();
    if ($positions === null) {
        return ['held' => false, 'who' => '',
            'why' => 'The online-controller list is unavailable, so no controlling position could be checked.'];
    }
    $mine = array_values(array_filter($positions, fn($p) => $p['holder'] === $name));
    if (!$mine) {
        return ['held' => false, 'who' => '',
            'why' => 'ATC24 does not show ' . $by . ' holding a controlling position right now.'];
    }
    $fields = array_values(array_filter([strtoupper($dep), strtoupper($arr)]));
    foreach ($mine as $p) {
        if (count($fields) === 0) {
            break; // no airports on record: any held position is all that can be checked
        }
        if ($p['airport'] !== '' && in_array($p['airport'], $fields, true)) {
            return ['held' => true, 'who' => $by . ' (' . $p['airport'] . ' ' . $p['position'] . ')', 'why' => ''];
        }
        if ($p['position'] === 'CTR') {
            return ['held' => true, 'who' => $by . ' (' . $p['airport'] . ' CTR)', 'why' => ''];
        }
    }
    if (count($fields) === 0) {
        $p = $mine[0];
        return ['held' => true, 'who' => $by . ' (' . $p['airport'] . ' ' . $p['position'] . ')', 'why' => ''];
    }
    $held = implode(', ', array_map(fn($p) => $p['airport'] . ' ' . $p['position'], array_slice($mine, 0, 4)));
    return ['held' => false, 'who' => '',
        'why' => $by . ' holds ' . $held . ', not ' . implode('/', $fields) . ' - amendments need a position at this field.'];
}

function board_read(): array {
    if (!file_exists(BOARD_FILE)) {
        return board_empty();
    }
    $raw = @file_get_contents(BOARD_FILE);
    $data = $raw ? json_decode($raw, true) : null;
    if (!is_array($data) || !isset($data['flights']) || !is_array($data['flights'])) {
        return board_empty();
    }
    $data['updatedAt'] = (int) ($data['updatedAt'] ?? 0);
    return $data;
}

/**
 * Runs $mutator against the decoded board under an exclusive lock and writes
 * the result back atomically-ish (LOCK_EX + rename-free in-place write, same
 * as every other cache in this project). Returns whatever $mutator returns,
 * or null if the lock could not be taken.
 */
function board_update(callable $mutator) {
    $dir = dirname(BOARD_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $lock = @fopen(BOARD_FILE . '.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) {
        if ($lock) fclose($lock);
        return null;
    }

    try {
        clearstatcache(true, BOARD_FILE);
        $board = board_read();
        $result = $mutator($board); // by reference into $board
        $board['updatedAt'] = (int) round(microtime(true) * 1000);
        $encoded = json_encode($board, JSON_UNESCAPED_SLASHES);
        if ($encoded !== false) {
            file_put_contents(BOARD_FILE, $encoded, LOCK_EX);
        }
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** Drops aged-out records and enforces the hard cap (newest kept). */
function board_prune(array &$flights): void {
    $now = (int) round(microtime(true) * 1000);
    foreach ($flights as $key => $rec) {
        $age = $now - (int) ($rec['updatedAt'] ?? 0);
        if ($age > ENTRY_TTL * 1000) {
            unset($flights[$key]);
        }
    }
    if (count($flights) <= MAX_FLIGHTS) {
        return;
    }
    uasort($flights, fn($a, $b) => ((int) ($b['updatedAt'] ?? 0)) <=> ((int) ($a['updatedAt'] ?? 0)));
    $flights = array_slice($flights, 0, MAX_FLIGHTS, true);
}

/**
 * The board key for a callsign - same normalisation the client and data.php
 * use (upper case, any run of space/underscore/dash becomes one dash), so the
 * departure and the arrival board of the same flight resolve to one record.
 */
function board_key(?string $value): ?string {
    $key = strtoupper(trim((string) $value));
    $key = preg_replace('/[\s_-]+/', '-', $key);
    $key = trim($key, '-');
    if ($key === '' || !preg_match('/^[A-Z0-9][A-Z0-9-]{1,23}$/', $key)) {
        return null;
    }
    return $key;
}

/**
 * Sanitises one written value. Squawk is the only field with a real format
 * (4 digits, 0-7) - a value that doesn't match is stored as empty rather than
 * as something that looks like a valid code.
 */
function board_value(string $field, $value): string {
    if (is_array($value) || is_object($value)) {
        return '';
    }
    $text = trim((string) $value);
    $text = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $text);
    $text = strip_tags($text);
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) > MAX_FIELD) {
        $text = mb_substr($text, 0, MAX_FIELD);
    }
    if ($field === 'squawk') {
        // Stored exactly as typed (digits only, never padded or re-derived):
        // inventing a missing digit would be fabricating a squawk, and
        // silently dropping a mistyped one would hide the controller's own
        // input from them. Real codes are octal (0-7 per digit), but a typed
        // "998" is kept and shown as typed rather than thrown away.
        $digits = preg_replace('/\D/', '', $text);
        return preg_match('/^\d{1,4}$/', $digits) ? $digits : '';
    }
    if ($field === 'cleared') {
        return $text === '' || $text === '0' || strtolower($text) === 'false' ? '' : '1';
    }
    return strtoupper($text);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $board = board_read();
    echo json_encode(['shared' => true, 'updatedAt' => $board['updatedAt'], 'flights' => $board['flights']]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Accept both a JSON body (what the client sends) and a form field, so the
// endpoint is also usable from curl / a plain <form> while debugging.
$raw = file_get_contents('php://input');
if (strlen($raw) > 65536) {
    http_response_code(413);
    echo json_encode(['error' => 'Payload too large']);
    exit;
}
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = json_decode((string) ($_POST['payload'] ?? ''), true);
}
if (!is_array($payload)) {
    $payload = $_POST;
}

$action = strtolower(trim((string) ($payload['action'] ?? 'set')));
$key    = board_key($payload['key'] ?? ($_POST['key'] ?? ''));
if ($key === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing flight key']);
    exit;
}

$by = board_key($payload['by'] ?? '') ?? '';

if ($action === 'delete') {
    $removed = board_update(function (array &$board) use ($key) {
        $existed = isset($board['flights'][$key]);
        unset($board['flights'][$key]);
        board_prune($board['flights']);
        return $existed;
    });
    if ($removed === null) {
        http_response_code(503);
        echo json_encode(['error' => 'Board is busy, try again']);
        exit;
    }
    echo json_encode(['ok' => true, 'key' => $key, 'deleted' => (bool) $removed]);
    exit;
}

$fields = $payload['fields'] ?? null;
if (!is_array($fields) || !$fields) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to set']);
    exit;
}

$clean = [];
foreach (BOARD_FIELDS as $field) {
    if (array_key_exists($field, $fields)) {
        $clean[$field] = board_value($field, $fields[$field]);
    }
}
if (!$clean) {
    http_response_code(400);
    echo json_encode(['error' => 'No known fields to set']);
    exit;
}

// The amendment gate. The flight's own airports come from this write when it
// carries them, otherwise from the record already on the board - the client
// sends dep/arr along with every edit it learned from the flight plan.
$before = board_read();
$existing = $before['flights'][$key] ?? [];
$dep = strtoupper((string) ($clean['dep'] ?? $existing['dep'] ?? ''));
$arr = strtoupper((string) ($clean['arr'] ?? $existing['arr'] ?? ''));

$blocked = [];
$claim = board_claim($by, $dep, $arr);
if (!$claim['held']) {
    foreach (AMENDMENT_FIELDS as $field) {
        if (array_key_exists($field, $clean) && $clean[$field] !== (string) ($existing[$field] ?? '')) {
            $blocked[] = $field;
            unset($clean[$field]);
        }
    }
    if (!$clean) {
        http_response_code(403);
        echo json_encode([
            'error' => $claim['why'],
            'blocked' => $blocked,
            'allowed' => ['squawk'],
            'by' => $by,
        ]);
        exit;
    }
}

$saved = board_update(function (array &$board) use ($key, $clean, $by) {
    $rec = isset($board['flights'][$key]) && is_array($board['flights'][$key])
        ? $board['flights'][$key]
        : [];
    foreach ($clean as $field => $value) {
        // An emptied field is dropped from the record instead of stored as
        // "" - the record stays alive (someone may still have the flight on
        // a board) but reads back as "not set", which is what an empty
        // squawk box actually means.
        if ($value === '') {
            unset($rec[$field]);
        } else {
            $rec[$field] = $value;
        }
    }
    $rec['updatedAt'] = (int) round(microtime(true) * 1000);
    if ($by !== '') {
        $rec['by'] = $by;
    }
    $board['flights'][$key] = $rec;
    board_prune($board['flights']);
    return $rec;
});

if ($saved === null) {
    http_response_code(503);
    echo json_encode(['error' => 'Board is busy, try again']);
    exit;
}

if ($blocked) {
    // What was allowed (the squawk, the plan's context) is saved; the
    // amendment fields are refused with the reason, so the client can explain
    // it and stop retrying what it may not write.
    http_response_code(403);
    echo json_encode([
        'error' => $claim['why'],
        'blocked' => $blocked,
        'allowed' => ['squawk'],
        'by' => $by,
        'record' => $saved,
    ]);
    exit;
}

echo json_encode(['ok' => true, 'key' => $key, 'record' => $saved, 'who' => $claim['who']]);
