<?php
// ATIS and the ATIS board, on one URL.
//
// This file has always been the ATIS *endpoint*: GET /atis upstream returns
// every airport at once, so atis.php fetches/caches that list and picks out one
// airport's letter for the radar's ATIS generator. That job is unchanged - same
// upstream, same 6s cache, same response shape - and it is still what
// `atis.php?icao=IRFD` returns.
//
// What is new is the other half of the same URL: opened in a browser (no
// parameters) it is now the ATIS BOARD - every published report, searchable by
// 4-letter airport code, refreshed once a second without reloading the page.
//
// The board reads the list through atis-list.php, the published-ATIS endpoint
// Centre and the planner already use, with its cache asked for one second
// (atis-list.php?max_age=1). This file fetches nothing new itself.

// The JSON half's own upstream and cache - unchanged ("const" cannot live
// inside the branch below in PHP).
const UPSTREAM_URL  = 'https://24data.ptfs.app/atis';
const CACHE_FILE    = __DIR__ . '/cache/atis.json';
const CACHE_MAX_AGE = 6; // seconds - matches 24data's recommended poll rate

// Which half is being asked for. A request with an airport code (or an explicit
// format=json) is the generator's JSON; anything else is a person opening the
// page.
$wantsJson = isset($_GET['icao']) || (isset($_GET['format']) && strtolower((string) $_GET['format']) === 'json');

if ($wantsJson) {
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . '/inc/upstream.php';

    $icao = strtoupper(trim($_GET['icao'] ?? ''));
    if (!preg_match('/^[A-Z]{4}$/', $icao)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid airport code']);
        exit;
    }

    $dir = dirname(CACHE_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $isStale = !file_exists(CACHE_FILE) || (time() - filemtime(CACHE_FILE)) >= CACHE_MAX_AGE;
    if ($isStale) {
        $lockHandle = fopen(CACHE_FILE . '.lock', 'c');
        if ($lockHandle && flock($lockHandle, LOCK_EX)) {
            clearstatcache(true, CACHE_FILE);
            $isStale = !file_exists(CACHE_FILE) || (time() - filemtime(CACHE_FILE)) >= CACHE_MAX_AGE;
            if ($isStale) {
                // ATASOFT: 24data's docs note this route can 503 if their
                // Discord bot is offline after a restart - don't let a
                // transient failure wipe out a still-good cached copy.
                $fresh = atasoft_fetch_upstream(UPSTREAM_URL);
                if ($fresh !== false) {
                    file_put_contents(CACHE_FILE, $fresh, LOCK_EX);
                }
            }
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    if (!file_exists(CACHE_FILE)) {
        http_response_code(502);
        echo json_encode(['error' => 'Upstream 24data.ptfs.app unavailable']);
        exit;
    }

    $atisList = json_decode(file_get_contents(CACHE_FILE), true);
    if (!is_array($atisList)) {
        http_response_code(502);
        echo json_encode(['error' => 'Upstream returned invalid data']);
        exit;
    }

    foreach ($atisList as $entry) {
        if (($entry['airport'] ?? null) === $icao) {
            echo json_encode(['airport' => $icao, 'letter' => $entry['letter'] ?? null]);
            exit;
        }
    }

    http_response_code(404);
    echo json_encode(['error' => 'ATIS data not available for this airport.']);
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>ATIS Board - ATC24 Radar - ATASOFT</title>
    <meta name="description" content="Every published ATIS report at once, searchable by 4-letter airport code, refreshed once a second without reloading the page." />
    <meta name="theme-color" content="#060814" />

    <link rel="icon" type="image/svg+xml" href="public/assets/favicon.svg" />
    <link rel="alternate icon" type="image/png" href="public/assets/icon.png" />
    <link rel="apple-touch-icon" href="public/assets/apple-touch-icon.png" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php require __DIR__ . '/inc/fonts.php'; ?>
    <link rel="stylesheet" href="public/style.css?v=<?= @filemtime(__DIR__ . '/public/style.css') ?: time() ?>">
    <link rel="stylesheet" href="public/style-planner.css?v=<?= @filemtime(__DIR__ . '/public/style-planner.css') ?: time() ?>">
    <link rel="stylesheet" href="public/style-atis.css?v=<?= @filemtime(__DIR__ . '/public/style-atis.css') ?: time() ?>">

    <script>
        // Same-origin backend, exactly like index.php and flight-planner.php.
        window.ATASOFT_API_HOST = null;
    </script>
    <script type="module" src="src/atisBoard.js?v=<?= @filemtime(__DIR__ . '/src/atisBoard.js') ?: time() ?>"></script>
</head>

<body class="fp-body at-body">
    <div id="top-bar" class="fp-topbar">
        <a id="name" href="index.php">ATC24 Radar <span style="opacity:.55; font-weight:500;">by ATASOFT</span></a>
        <div class="fp-nav">
            <a class="menu-button fp-navlink" href="index.php">&larr; RADAR</a>
            <a class="menu-button fp-navlink" href="flight-planner.php">FLIGHT PLANNER</a>
            <a class="menu-button fp-navlink" href="follow-flight.php">FOLLOW FLIGHT</a>
            <span class="menu-button fp-navlink fp-navlink-active">ATIS</span>
        </div>
        <div class="fp-topbar-spacer"></div>
        <div id="at-status" class="atasoft-status" title="Published ATIS connection status">
            <span class="atasoft-status__dot" data-state="connecting"></span>
            <span class="atasoft-status__label">CONNECTING</span>
        </div>
    </div>

    <main class="at-main">
        <!-- The bar the whole page is about: one code, one list. The list itself
             is refreshed once a second in the browser (no reload, no form). -->
        <section class="at-bar">
            <label class="at-search">
                <span class="at-search-label">AIRPORT</span>
                <input id="at-search" class="at-search-input" type="text" inputmode="text" autocapitalize="characters"
                    autocomplete="off" spellcheck="false" maxlength="4" placeholder="IRFD"
                    aria-label="Search by 4-letter airport code" />
            </label>
            <button id="at-clear" class="fp-mini" type="button" title="Clear the filter">CLEAR</button>
            <span id="at-count" class="at-count">loading&hellip;</span>
            <span class="at-bar-spacer"></span>
            <button id="at-live" class="fp-mini fp-toggle is-on" type="button"
                title="Fetch the published ATIS once a second. Pause to stop polling in this tab.">AUTO 1s</button>
            <button id="at-refresh" class="fp-mini" type="button" title="Fetch the published ATIS now">REFRESH</button>
        </section>

        <div id="at-note" class="fp-hint">
            Every published report, verbatim, from the same ATIS list Centre mode and the
            flight planner read (atis-list.php). Nothing here is reformatted: the runway
            fields and QNH are shown beside the text they were read out of.
        </div>

        <div id="at-list" class="at-list" aria-live="polite"></div>
    </main>

    <a id="credits" href="about.html">Credits &amp; License · ATASOFT</a>
</body>

</html>
